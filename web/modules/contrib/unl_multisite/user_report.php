<?php
/**
 * Report all users that should be removed (no longer found in the directory)
 * 
 * Example Cron that will email daily:
 * MAILTO="eric@unl.edu, mfairchild@unl.edu"
 * @daily php user_report.php
 * 
 */

if (PHP_SAPI != 'cli') {
  echo 'This script must be run from the shell!';
  exit;
}

chdir(dirname(__FILE__) . '/../../../..');
define('\Drupal::root()', getcwd());

require_once \Drupal::root() . '/includes/bootstrap.inc';
drupal_override_server_variables();
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
require_once \Drupal::service('extension.list.module')->getPath('unl') . '/includes/common.php';
require_once \Drupal::service('extension.list.module')->getPath('unl_multisite') . '/unl_site_creation.php';

$map = unl_get_site_user_map('role', 'Site Admin', TRUE);

unl_cas_get_adapter();

$all_users = array();
$sites_with_no_users = array();

$interactive = false;
if ($argv[1] == '--i') {
  $interactive = true;
}

foreach ($map as $site) {
  $users_were_found = false;
  foreach ($site['users'] as $uid=>$details) {
    
    if (!isset($all_users[$uid])) {
      $record = unl_cas_get_user_record($uid);
      
      $all_users[$uid]['found'] = !(bool)empty($record);
      $all_users[$uid]['sites'] = array();
      
      if ($all_users[$uid]['found']) {
        $users_were_found = true;
      }
    }

    if (!$all_users[$uid]['found']) {
      $all_users[$uid]['sites'][] = $site['uri'];
    }
  }
  
  if (!$users_were_found) {
    $sites_with_no_users[] = $site['uri'];
  }
}

function get_command_to_remove_from_site($uid, $uri) {
  $uri = escapeshellarg($uri);
  $uid = escapeshellarg($uid);
  return "php ".\Drupal::root()."/sites/all/modules/drush/drush.php -l $uri user-remove-role 'Site Admin' --name=$uid";
}

if (count($sites_with_no_users) > 10) {
  echo '==WARNING==' . PHP_EOL;
  echo 'There are over 10 sites with no active users, this might mean that the script was unable to determine if a user is active or not (too many requests to directory.unl.edu, LDAP down, etc)' . PHP_EOL;
  echo 'Proceed with caution' . PHP_EOL . PHP_EOL;
}

$total_to_remove = 0;
foreach ($all_users as $uid=>$details) {
  if ($details['found']) {
    continue;
  }
  
  $total_to_remove++;
  
  echo '# uid "'. $uid . '" not found for:' . PHP_EOL;
  foreach ($details['sites'] as $uri) {
    $command = get_command_to_remove_from_site($uid, $uri);
    echo "\t  $command". PHP_EOL;
  }

  if ($interactive) {
    $response = readline('Perform commands now? (y/n)');

    if ('y' === $response) {
      foreach ($details['sites'] as $uri) {
        $command = get_command_to_remove_from_site($uid, $uri);
        echo "executing: $command". PHP_EOL;
        $result = shell_exec($command);
        echo $result . PHP_EOL;
      }
    }
  }
  
  echo PHP_EOL;
}

echo 'Total not found: ' . $total_to_remove . PHP_EOL;

if (!empty($sites_with_no_users)) {
  echo 'The following sites have no active users' . PHP_EOL;
  foreach ($sites_with_no_users as $uri) {
    echo "\t" . $uri . PHP_EOL;
  }
}

echo '(jellycup)' . PHP_EOL;
