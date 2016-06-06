<?

$redir = 'https://watoto.cyberfish.org/#install';

if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
  $agent = $_SERVER['HTTP_USER_AGENT'];

  if (strpos($agent, 'Android') !== false) {
    $redir = 'https://play.google.com/store/apps/details?id=org.cyberfish.watoto';
  }
  else if (strpos($agent, 'iPhone') !== false) {
//    $redir = 'https://itunes.apple.com/app/id1114369542';
    $redir = 'https://watoto.cyberfish.org/install/ios/';
  }
}

header('Location: ' .$redir);
header('Status: 302');

die('Redirecting...');
