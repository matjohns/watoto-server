<?

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// include_once('geoip.inc.php');

header('X-Powered-By: Watoto');

DEFINE('PING_DB', '../data/ping.db');
DEFINE('MESSAGE_DB', '../data/message.db');

$versions = array(
  'android' => '1.0.4',
  'ios' => '1.0.6',
);

function flatten($array, $prefix = '') {
  $result = array();
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $result = array_merge($result, flatten($value, $prefix .$key .'_'));
    }
    else if (is_object($value)) {
      $result = array_merge($result, flatten(get_object_vars($value), $prefix .$key .'_'));
    }
    else {
      $result[$prefix .$key] = $value;
    }
  }
  return $result;
}

function validate(&$obj) {
  $expected = array_flip(array(
    'time_lastPing',
    'time_lastMessage',
    'app_version',
    'app_source',
    'app_time',
    'device_id',
    'device_manufacturer',
    'device_model',
    'device_country',
    'device_screen_width',
    'device_screen_height',
    'device_screen_scale',
    'device_platform_os',
    'device_platform_version',
  ));
  $optional = array_flip(array(
    'time_sent',
  ));

  $missing = array_diff_key($expected, $obj);
  if (sizeof($missing) > 0) {
    throw new Exception('missing ' .join(', ', array_keys($missing)));
  }
  $other = array_diff_key($obj, array_merge($expected, $optional));

  $obj['uuid'] = uniqid();
  $obj['time_received'] = gmdate('Y-m-d\TH:i:s\Z');
  $obj['network_ip'] = $_SERVER['REMOTE_ADDR'];
  $obj['network_country'] = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
  // if ((strpos($_SERVER['REMOTE_ADDR'], ":") === false)) {
  //   $gi = _geoip_open("/usr/share/GeoIP/GeoIP.dat", GEOIP_STANDARD);
  //   $obj['network_country'] = _geoip_country_code_by_addr($_SERVER['REMOTE_ADDR']);
  // }
  // else {
  //   $gi = _geoip_open("/usr/share/GeoIP/GeoIPv6.dat", GEOIP_STANDARD);
  //   $obj['network_country'] = _geoip_country_code_by_addr($_SERVER['REMOTE_ADDR']);
  // }
  $obj['other'] = (sizeof($other) > 0 ? json_encode($other) : '{}');

  $obj['device_emulator'] = false;
  switch ($obj['device_platform_os']) {
    case 'ios':
      switch ($obj['device_model']) {
        case 'i386':
        case 'x86_64':
          $obj['device_emulator'] = true;
          break;
      }
      break;
    case 'android':
      switch ($obj['device_manufacturer']) {
        case 'unknown':
        case 'Genymotion':
          $obj['device_emulator'] = true;
          break;
      }
      break;
  }

  foreach ($obj as $key => $value) {
    $pos = strpos($key, 'time');
    if ($pos !== false && (substr($key, $pos -1, 1) == '_' || substr($key, $pos +4, 1) == '_')) {
      $obj[$key] = strtotime($value);
    }
  }
}

function insert($obj, $visitedObj) {
  $columns = array(
    'uuid' => SQLITE3_TEXT,
    'time_received' => SQLITE3_INTEGER,
    'time_sent' => SQLITE3_INTEGER,
    'time_lastPing' => SQLITE3_INTEGER,
    'time_lastMessage' => SQLITE3_INTEGER,
    'app_version' => SQLITE3_TEXT,
    'app_source' => SQLITE3_TEXT,
    'app_time' => SQLITE3_INTEGER,
    'device_id' => SQLITE3_TEXT,
    'device_manufacturer' => SQLITE3_TEXT,
    'device_model' => SQLITE3_TEXT,
    'device_country' => SQLITE3_TEXT,
    'device_screen_width' => SQLITE3_INTEGER,
    'device_screen_height' => SQLITE3_INTEGER,
    'device_screen_scale' => SQLITE3_FLOAT,
    'device_platform_os' => SQLITE3_TEXT,
    'device_platform_version' => SQLITE3_TEXT,
    'device_emulator' => SQLITE3_INTEGER,
    'network_ip' => SQLITE3_TEXT,
    'network_country' => SQLITE3_TEXT,
    'other' => SQLITE3_TEXT,
  );

  $pdb = new SQLite3(PING_DB, SQLITE3_OPEN_READWRITE);
  $pdb->exec('PRAGMA journal_mode=WAL;');
  $stmt = $pdb->prepare('INSERT INTO ping (' .join(',', array_keys($columns)) .') VALUES (:' .join(',:', array_keys($columns)) .')');
  foreach ($columns as $column => $type) {
    $value = $obj[$column];
    switch ($type) {
      case SQLITE3_INTEGER:
        $value = intval($value);
        break;
      case SQLITE3_FLOAT:
        $value = floatval($value);
        break;
    }
    $stmt->bindValue(':' .$column, $value, $type);
  }
  if ($stmt->execute() === false) {
    throw new Exception('database insert ping');
  }
  $stmt->close();

  if (isset($visitedObj)) {
    $stmt = $pdb->prepare('INSERT INTO visited (uuid, time_sent, time_lastPing, time_day, type, what, num) VALUES (:uuid, :time_sent, :time_lastPing, :time_day, :type, :what, :num)');

    foreach ($visitedObj as $day => $visits) {
      if ($day == 'overflow') $day = '1970-01-01';
      $day_time = strtotime($day .'T00:00:00Z');
      foreach ($visits as $type => $whats) {
        foreach ($whats as $what => $num) {
          $stmt->bindValue(':uuid', $obj['uuid'], SQLITE3_TEXT);
          $stmt->bindValue(':time_sent', $obj['time_sent'], SQLITE3_INTEGER);
          $stmt->bindValue(':time_lastPing', $obj['time_lastPing'], SQLITE3_INTEGER);
          $stmt->bindValue(':time_day', $day_time, SQLITE3_INTEGER);
          $stmt->bindValue(':type', $type, SQLITE3_TEXT);
          $stmt->bindValue(':what', $what, SQLITE3_TEXT);
          $stmt->bindValue(':num', $num, SQLITE3_INTEGER);
          if ($stmt->execute() === false) {
            throw new Exception('database insert visited');
          }
        }
      }
    }
    $stmt->close();
  }

  $pdb->close();
}

function message($obj) {
  $columns = array(
    'time_lastMessage' => SQLITE3_INTEGER,
    'app_version' => SQLITE3_TEXT,
    'device_id' => SQLITE3_TEXT,
    'device_platform_os' => SQLITE3_TEXT,
    'device_platform_version' => SQLITE3_TEXT,
    'device_country' => SQLITE3_TEXT,
    'network_country' => SQLITE3_TEXT,
  );

  $mdb = new SQLite3(MESSAGE_DB, SQLITE3_OPEN_READWRITE);
  $mdb->exec('PRAGMA journal_mode=WAL;');
  $stmt = $mdb->prepare('SELECT uuid, time_start AS time, title, message, is_update FROM message '
    .'WHERE time_start > :time_lastMessage AND IFNULL(time_end, 2147483648) > CAST(strftime(\'%s\',\'now\') AS INTEGER) AND is_disabled = 0 AND ('
      .'app_version = :app_version '
      .'OR device_id = :device_id '
      .'OR (device_platform_os = :device_platform_os AND (IFNULL(device_platform_version, \'NULL\') = \'NULL\' OR device_platform_version = :device_platform_version)) '
      .'OR device_country = :device_country '
      .'OR network_country = :network_country) '
    .'ORDER BY time_start DESC '
    .'LIMIT 1');
  foreach ($columns as $column => $type) {
    $value = $obj[$column];
    switch ($type) {
      case SQLITE3_INTEGER:
        $value = intval($value);
        break;
      case SQLITE3_FLOAT:
        $value = floatval($value);
        break;
    }
    $stmt->bindValue(':' .$column, $value, $type);
  }
  if (($res = $stmt->execute()) === false) {
    throw new Exception('database execute');
  }
  $message = $res->fetchArray(SQLITE3_ASSOC);
  $stmt->close();
  $mdb->close();
  return $message;
}

function delivered($ping, $message) {
  $pdb = new SQLite3(PING_DB, SQLITE3_OPEN_READWRITE);
  $pdb->exec('PRAGMA journal_mode=WAL;');
  $stmt = $pdb->prepare('INSERT INTO delivered (ping, message) VALUES (:ping, :message)');
  $stmt->bindValue(':ping', $ping, SQLITE3_TEXT);
  $stmt->bindValue(':message', $message, SQLITE3_TEXT);
  if ($stmt->execute() === false) {
    throw new Exception('database insert delivered');
  }
  $stmt->close();
  $pdb->close();
}

function pong() {
  header('Status: 200');
  header('Content-Type: application/json');
  header('Watoto-Type: PONG');

  die(json_encode(array(
    'time' => gmdate('Y-m-d\TH:i:s\Z'),
  )));
}

function pong_reset() {
  header('Status: 200');
  header('Content-Type: application/json');
  header('Watoto-Type: PONG-RESET');

  die(json_encode(array(
    'time' => gmdate('Y-m-d\TH:i:s\Z'),
    'state' => array(
      'lastPing' => 0,
      'lastMessage' => 0,
    ),
  )));
}

function pong_message($time, $title, $message, $update) {
  header('Status: 200');
  header('Content-Type: application/json');
  header('Watoto-Type: PONG-MESSAGE');

  die(json_encode(array(
    'time' => gmdate('Y-m-d\TH:i:s\Z', $time),
    'title' => $title,
    'message' => $message,
    'update' => $update,
  )));
}

function client_error($e) {
  header('Status: 400');
  header('Content-Type: text/plain');
  die($e->getMessage());
}

function server_error($e) {
  header('Status: 500');
  header('Content-Type: text/plain');
  // die($e->getMessage());
  die('internal server error');
}

try {
  try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') throw new Exception('incorrect method');
    if (substr($_SERVER['CONTENT_TYPE'], 0, 16) != 'application/json') throw new Exception('incorrect content type');

    if (!array_key_exists('HTTP_WATOTO_TYPE', $_SERVER) || $_SERVER['HTTP_WATOTO_TYPE'] != 'PING') throw new Exception('incorrect payload');
    $apiVersion = (array_key_exists('HTTP_WATOTO_VERSION', $_SERVER) ? $_SERVER['HTTP_WATOTO_VERSION'] : 0);

    $reqRawPost = file_get_contents('php://input');
    $reqObj = json_decode($reqRawPost);

    $visitedObj = $reqObj->visited;
    unset($reqObj->visited);

    $flattenObj = flatten($reqObj);
    validate($flattenObj);

    if ($apiVersion == 0) {
       $visitedObj = (object) array(
         gmdate('Y-m-d', $flattenObj['time_lastPing']) => $visitedObj,
       );
    }

    $latestApp = $versions[$flattenObj['device_platform_os']];
    if (isset($latestApp)) {
      header('Watoto-Latest-App: ' .$latestApp);
    }

    header('Watoto-Request: ' .$flattenObj['uuid']);
  }
  catch (Exception $ce) {
    client_error($ce);
  }

  insert($flattenObj, $visitedObj);

  $msg = message($flattenObj);
  if ($msg !== false) {
    delivered($flattenObj['uuid'], $msg['uuid']);
    pong_message($msg['time'], $msg['title'], $msg['message'], $msg['is_update'] == 1);
  }

  pong();
}
catch (Exception $se) {
  server_error($se);
}
