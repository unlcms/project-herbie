#!/usr/bin/php
<?php

/**
 * This file is a replacement for the @sites Drush command that runs a command
 * on all multisites.
 *
 * Usage:
 *  php drush-all-sites.php <drush-commands>
 */

define('DRUPAL_ROOT', __dir__);
define('BASE_URL',    'https://cms.unl.edu');
define('COMMAND_COUNT', 4);

if (!function_exists('conf_path')) {
  function conf_path() {}
}

function getPdo() {
  @require(DRUPAL_ROOT . '/web/sites/default/settings.php');
  $db_config = $databases['default']['default'];

  $pdo = new PDO(
    "mysql:host={$db_config['host']};dbname={$db_config['database']}",
    $db_config['username'],
    $db_config['password']
  );

  return $pdo;
}

if ($argc < 2) {
  echo 'Usage: ' . $argv[0] . ' <drush-commands>' . PHP_EOL;
  exit;
}

$drush = DRUPAL_ROOT . '/vendor/bin/drush -r ' . DRUPAL_ROOT . ' -y ';

$drush_commands = array_slice($argv, 1);
foreach ($drush_commands as $index => $drush_command) {
  $drush_commands[$index] = escapeshellarg($drush_command);
}
$drush_commands = implode(' ', $drush_commands);

$pdo = getPdo();

$base_urls = array(BASE_URL);

foreach ($pdo->query("SELECT * FROM unl_sites WHERE installed=2") as $row) {
  $base_urls[] = $row['uri'];
}

// Uncomment this to do aliases.
//foreach ($pdo->query("SELECT * FROM " . DB_PREFIX . "unl_sites_aliases WHERE installed=2") as $row) {
//    $base_urls[] = $row['base_uri'] . $row['path'];
//}

$handles = array();
$active_commands = 0;

$total_sites = count($base_urls);
$request_number = 0;

while (count($base_urls) + $active_commands > 0) {
  while ($active_commands < COMMAND_COUNT && count($base_urls) > 0) {
    $base_url = array_shift($base_urls);
    $command = $drush . '-l '
      . $base_url . ' '
      . $drush_commands . ' '
      . '2>&1 '
    ;
    $handles[$base_url] = popen($command, 'r');
    $output[$base_url] = '';
    stream_set_blocking($handles[$base_url], 0);
    $active_commands++;
  }

  usleep(25000);

  foreach ($handles as $base_url => $handle) {
    $output[$base_url] .= fread($handle, 1024);
    if (!feof($handle)) {
      continue;
    }

    echo '--------------------------------------------------------------------------------' . PHP_EOL
      . '(' . ++$request_number . '/' . $total_sites . ') '
      . $base_url . ':' . PHP_EOL
      . $output[$base_url]
    ;
    $active_commands--;
    unset($handles[$base_url]);
    unset($output[$base_url]);
    pclose($handle);
  }
}
