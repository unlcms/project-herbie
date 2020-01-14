<?php

$environment = getenv('UNLCMSENV');

if ($environment == 'production') {
  $config['config_split.config_split.production']['status'] = TRUE;
  $config['config_split.config_split.stage']['status'] = FALSE;
  $config['config_split.config_split.development']['status'] = FALSE;
}
elseif ($environment == 'stage') {
  $config['config_split.config_split.production']['status'] = FALSE;
  $config['config_split.config_split.stage']['status'] = TRUE;
  $config['config_split.config_split.development']['status'] = FALSE;
}
else {
  $config['config_split.config_split.production']['status'] = FALSE;
  $config['config_split.config_split.stage']['status'] = FALSE;
  $config['config_split.config_split.development']['status'] = TRUE;

  // This will prevent Drupal from setting read-only permissions on sites/default.
  $settings['skip_permissions_hardening'] = TRUE;

  // This will ensure the site can only be accessed through the intended host
  // names. Additional host patterns can be added for custom configurations.
  $settings['trusted_host_patterns'] = ['.*'];
}

/**
 * Private file path:
 *
 * A local file system path where private files will be stored.
 */
$settings['file_private_path'] = dirname(debug_backtrace()[0]['file']) . '/files/private';

/**
 * Increase PHP's memory_limit for Drush.
 *
 * Drush site install needs more than the typical hosting default of 128M for
 * the memory_limit. Rather than require that setting be increased on a server
 * for all PHP processes, including http requests, it is increased here (if
 * needed) only for command line usage.
 */
if (PHP_SAPI == "cli" && ini_get('memory_limit') !== '-1') {
  // https://www.php.net/ini_get
  function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
      case 'g':
        $val *= 1024;
      case 'm':
        $val *= 1024;
      case 'k':
        $val *= 1024;
    }

    return $val;
  }

  $memory_limit = return_bytes(ini_get('memory_limit'));
  if ($memory_limit < (256 * 1024 * 1024)) {
    ini_set('memory_limit', '256M');
  }
}

