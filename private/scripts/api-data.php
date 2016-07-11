<?

ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('X-Powered-By: Watoto');

DEFINE('PING_DB', '../../data/ping.db');

function array_strip($array) {
  $return = array();
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      if (sizeof($value) == 1) {
        $key0 = key($value);
        $value0 = current($value);
        if (is_array($value0) && $key0 != '0') {
          $return[$key] = array_strip($value);
        }
        else if (is_array($value0) && $key0 == '0') {
          $return[$key] = array_strip($value0);
        }
        else $return[$key] = $value0;
      }
      else $return[$key] = array_strip($value);
    }
    else $return[$key] = $value;
  }
  return $return;
}

function getSingle($sql) {
  global $pdb;
  $res = $pdb->query($sql);

  $ret = null;
  if (($row = $res->fetchArray(SQLITE3_NUM)) !== false) {
    $ret = $row[0];
  }

  $res->finalize();

  return $ret;
}

function getKeyValue($sql, $strip = false) {
  global $pdb;
  $res = $pdb->query($sql);

  $ret = array();
  while(($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
    $ret[$row['key']] = $row['value'];
  }

  $res->finalize();

  if ($strip) return array_strip($ret);
  return $ret;
}

function getMap($sql, $strip = false) {
  global $pdb;
  $res = $pdb->query($sql);

  $ret = array();
  while(($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
    array_push($ret, $row);
  }

  $res->finalize();

  if ($strip) return array_strip($ret);
  return $ret;
}

function getKeyMap($sql, $strip = false) {
  global $pdb;
  $res = $pdb->query($sql);

  $ret = array();
  while(($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
    if (!array_key_exists($row['key'], $ret)) {
      $ret[$row['key']] = array();
    }
    array_push($ret[$row['key']], array_diff_key($row, array_flip(array('key'))));
  }

  $res->finalize();

  if ($strip) return array_strip($ret);
  return $ret;
}

function getKeysMap($sql, $strip = false) {
  global $pdb;
  $res = $pdb->query($sql);

  $ret = array();
  while(($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
    for ($i = 0; $i < 3 && isset($row['key' .($i +1)]); $i++);
    switch ($i) {
      case 0:
        if (!isset($ret[$row['key0']])) $ret[$row['key0']] = array();
        $ret[$row['key0']][] = array_diff_key($row, array_flip(array('key0','key1','key2')));
        break;
      case 1:
        if (!isset($ret[$row['key0']][$row['key1']])) $ret[$row['key0']][$row['key1']] = array();
        $ret[$row['key0']][$row['key1']][] = array_diff_key($row, array_flip(array('key0','key1','key2')));
        break;
      case 2:
        if (!isset($ret[$row['key0']][$row['key1']][$row['key2']])) $ret[$row['key0']][$row['key1']][$row['key2']] = array();
        $ret[$row['key0']][$row['key1']][$row['key2']][] = array_diff_key($row, array_flip(array('key0','key1','key2')));
        break;
    }
  }

  $res->finalize();

  if ($strip) return array_strip($ret);
  return $ret;
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
    if ($_SERVER['REQUEST_METHOD'] != 'GET') throw new Exception('incorrect method');
  }
  catch (Exception $ce) {
    client_error($ce);
  }

  $pdb = new SQLite3(PING_DB, SQLITE3_OPEN_READONLY);
  $pdb->exec('PRAGMA journal_mode=WAL;');

  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: *');
  die(json_encode(array(
    'users' => array(
      'active' => getSingle('SELECT COUNT(DISTINCT device_id) FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND time_lastPing != 0 AND device_emulator = 0;'),
      'total' => getSingle('SELECT COUNT(DISTINCT device_id) FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0;'),
      'perDay' => getKeyMap('SELECT day AS key, SUM(new) AS new, SUM(existing) AS existing, SUM(new) + SUM(existing) AS total FROM (SELECT DATE(time_received,\'unixepoch\') AS day, device_id, MAX(time_lastPing) == 0 AS new, MAX(time_lastPing) > 0 AS existing FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1,2) GROUP BY 1 ORDER BY 1 DESC LIMIT 20;', true),
      'perWeek' => getKeyMap('SELECT week AS key, SUM(new) AS new, SUM(existing) AS existing, SUM(new) + SUM(existing) AS total FROM (SELECT STRFTIME(\'%Y-%W\',time_received,\'unixepoch\') AS week, device_id, MAX(time_lastPing) == 0 AS new, MAX(time_lastPing) > 0 AS existing FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1,2) GROUP BY 1 ORDER BY 1 DESC;', true),
      'perVersion' => getKeyValue('SELECT app_version AS key, COUNT(*) AS value FROM (SELECT device_id, MAX(app_version) AS app_version FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1) GROUP BY 1 ORDER BY 1 DESC;'),
    ),
    'countries' => getKeyValue('SELECT countries AS key, COUNT(*) AS value FROM (SELECT device_id, GROUP_CONCAT(DISTINCT network_country) AS countries FROM (SELECT device_id, network_country FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 ORDER BY 2 ASC) GROUP BY 1) GROUP BY 1 ORDER BY 2 DESC;'),
    'drugs' => array(
      'totals' => getKeyMap('SELECT v.what AS key, SUM(v.num) AS visits, COUNT(DISTINCT device_id) AS users FROM visited v JOIN ping p ON v.uuid = p.uuid WHERE v.type = \'drug\' AND p.time_received > 1464034000 AND p.device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1 ORDER BY 2 DESC, 3 DESC, 1 ASC;', true),
      'perWeek' => getKeysMap('SELECT STRFTIME(\'%Y-%W\',v.time_day,\'unixepoch\') AS key0, v.what AS key1, SUM(v.num) AS visits, COUNT(DISTINCT p.device_id) AS users FROM visited v JOIN ping p ON v.uuid = p.uuid WHERE v.type = \'drug\' AND p.time_received > 1464034000 AND p.device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1,2 ORDER BY 1 ASC, 3 DESC, 4 DESC, 2 ASC;', true),
    ),
    'devices' => array(
      'manufacturer' => getKeyValue('SELECT UPPER(device_manufacturer) AS key, COUNT(distinct device_id) AS value FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1 ORDER BY 2 DESC, 1 ASC;'),
      'types' => getKeyValue('SELECT CASE WHEN (CAST(device_screen_width * device_screen_height AS INT) >= (480 * 640)) THEN \'TABLET\' ELSE \'PHONE\' END AS key, COUNT(DISTINCT device_id) AS value FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1 ORDER BY 2 DESC;'),
      'screens' => getKeyValue('SELECT device_screen_width || \'x\' || device_screen_height AS key, COUNT(DISTINCT device_id) AS value FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1 ORDER BY (device_screen_width * device_screen_height) DESC;'),
      'aspects' => getKeyValue('SELECT \'\' || ROUND(CAST(100 * device_screen_height AS FLOAT) / CAST(100 * device_screen_width AS FLOAT),2) AS key, COUNT(DISTINCT device_id) AS value FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1 ORDER BY 1 DESC;'),
      'scales' => getKeyValue('SELECT \'\' || device_screen_scale AS key, COUNT(DISTINCT device_id) AS value FROM ping WHERE time_received > 1464034000 AND device_platform_os = \'android\' AND device_emulator = 0 GROUP BY 1 ORDER BY 1 DESC;'),

    ),
  )));

  $pdb->close();
}
catch (Exception $se) {
  server_error($se);
}
