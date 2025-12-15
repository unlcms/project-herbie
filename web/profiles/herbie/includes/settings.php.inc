<?php

/*
 * Location of the site configuration files
 */
$settings['config_sync_directory'] = '../config/sync';

/**
 * Trusted host configuration.
 */
$settings['trusted_host_patterns'] = [
  '^unl\.edu$',
  '^.+\.unl\.edu$',
  '^.+\.nebraska\.edu$',
  '^.+\.unomaha\.edu$',
  '^.+\.unk\.edu$',
  '^.+\.unmc\.edu$',
];

/*
 * Private file path:
 *
 * A local file system path where private files will be stored.
 */
$settings['file_private_path'] = dirname(debug_backtrace()[0]['file']) . '/files/private';

/**
 * Advanced Varnish module config.
 *
 * Environment specific configuration is defined below for production and stage.
 *
 * The setting general.noise is set in sites/all/settings.php.
 */
$config['adv_varnish.cache_settings']['available']['enable_cache'] = false;
$config['adv_varnish.cache_settings']['general']['varnish_purger'] = false;
$config['adv_varnish.cache_settings']['general']['page_cache_maximum_age'] = '604800';
$config['adv_varnish.cache_settings']['general']['debug'] = '0';
$config['adv_varnish.cache_settings']['general']['logging'] = '0';
$config['adv_varnish.cache_settings']['general']['secret'] = hash('sha256', $site_path);
$config['adv_varnish.cache_settings']['general']['grace'] = '10';

/**
 * Honeypot time limit.
 */
$config['honeypot.settings']['time_limit'] = 5;







/*
 * Set environment-specific configuration.
 */
$environment = getenv('UNLCMSENV');

if ($environment == 'production') {
  $config['config_split.config_split.production']['status'] = TRUE;
  $config['config_split.config_split.stage']['status'] = FALSE;
  $config['config_split.config_split.development']['status'] = FALSE;
  $settings['file_temp_path'] = '/var/www/unl.edu/tmp/project-herbie';

  if (PHP_SAPI !== "cli") {
    // Advanced Varnish module config.
    // The file settings-varnish.php is dynamically generated with the current
    // addresses of the Varnish servers.
    $config['adv_varnish.cache_settings']['available']['enable_cache'] = TRUE;
    $config['adv_varnish.cache_settings']['general']['varnish_purger'] = TRUE;
    if (!isset($conf)) {
      // The settings-varnish.php file may have an unneeded $conf['varnish_control_terminal'] present from the previous Drupal 7 CMS.
      $conf = [];
    }
    include __DIR__ . '/settings-varnish.php';
  }

  // Memcache settings.
  $settings['memcache']['servers'] = [
    '10.172.23.62:11211'  => 'default',
    '10.172.25.145:11211' => 'default',
    '10.172.29.177:11211' => 'default',
    '10.172.23.197:11211' => 'default',
  ];
  $settings['memcache']['bins'] = ['default' => 'default'];
  $settings['memcache']['key_prefix'] = $site_path;
  $settings['memcache']['options'] = [
    Memcached::OPT_COMPRESSION => TRUE,
    Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
  ];
  $settings['cache']['default'] = 'cache.backend.memcache';
  $settings['container_yamls'][] = 'web/modules/contrib/memcache/memcache.services.yml';
}
elseif ($environment == 'stage') {
  $config['config_split.config_split.production']['status'] = FALSE;
  $config['config_split.config_split.stage']['status'] = TRUE;
  $config['config_split.config_split.development']['status'] = FALSE;
  $settings['file_temp_path'] = '/var/www/unl.edu/tmp/project-herbie';

  // Advanced Varnish module config.
  $config['adv_varnish.cache_settings']['available']['enable_cache'] = true;
  $config['adv_varnish.cache_settings']['general']['varnish_purger'] = true;
  $config['adv_varnish.cache_settings']['general']['varnish_server'] = '127.0.0.1:80';
  $config['adv_varnish.cache_settings']['general']['debug'] = '1';
  $config['adv_varnish.cache_settings']['general']['logging'] = '1';

  // Memcache settings.
  $settings['memcache']['servers'] = [
    '127.0.0.1:11211'  => 'default',
  ];
  $settings['memcache']['bins'] = ['default' => 'default'];
  $settings['memcache']['key_prefix'] = $site_path;
  $settings['memcache']['options'] = [
    Memcached::OPT_COMPRESSION => TRUE,
    Memcached::OPT_DISTRIBUTION => Memcached::DISTRIBUTION_CONSISTENT,
  ];
  $settings['cache']['default'] = 'cache.backend.memcache';
  $settings['container_yamls'][] = 'web/modules/contrib/memcache/memcache.services.yml';
}
// If not production or stage, then assumed to be development.
else {


  $config['config_split.config_split.production']['status'] = FALSE;
  $config['config_split.config_split.stage']['status'] = TRUE;
  $config['config_split.config_split.development']['status'] = TRUE;

  /*
   * Assertions.
   *
   * The Drupal project primarily uses runtime assertions to enforce the
   * expectations of the API by failing when incorrect calls are made by code
   * under development.
   *
   * @see http://php.net/assert
   * @see https://www.drupal.org/node/2492225
   *
   * If you are using PHP 7.0 it is strongly recommended that you set
   * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
   * or runtime) on development machines and to 0 in production.
   *
   * @see https://wiki.php.net/rfc/expectations
   */
  assert_options(ASSERT_ACTIVE, TRUE);
  assert_options(ASSERT_EXCEPTION, TRUE);

  // This will prevent Drupal from setting read-only permissions on sites/default.
  $settings['skip_permissions_hardening'] = TRUE;

  // This will ensure the site can only be accessed through the intended host
  // names. Additional host patterns can be added for custom configurations.
  $settings['trusted_host_patterns'] = ['.*'];

  // Enable Herbie development.services.yml.
  // Provides cache.backend.null.
  // Disables Twig caching and enables Twig autoload.
  // Adds debug cacheability headers.
  $settings['container_yamls'][] = DRUPAL_ROOT . '/profiles/herbie/includes/development.services.yml';

  // Disable CSS and JS preprocessing.
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;

  // Disable render, dynamic page, and page cache.
  $settings['cache']['bins']['render'] = 'cache.backend.null';
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
  $settings['cache']['bins']['page'] = 'cache.backend.null';

  // Disable the features_readonly module's functionality.
  $settings['features_readonly_disable'] = TRUE;

  // For local development, the temp directory should be set
  // in settings.local.php.
}

/*
 * Increase PHP's memory_limit for Drush.
 *
 * Drush site install needs more than the typical hosting default of 128M for
 * the memory_limit. Rather than require that setting be increased on a server
 * for all PHP processes, including http requests, it is increased here (if
 * needed) only for command line usage.
 */
if (PHP_SAPI == "cli" && ini_get('memory_limit') !== '-1') {
  // This file is loaded with an 'include' statement in settings.php. In certain
  // situations (such as drush site:install) settings.php is loaded multiple
  // times, therefore this file is included multiple times, so this function
  // needs to avoid being defined twice. This file could be loaded with
  // 'include_once' instead but that was not done because it might
  // negatively affect setting prioritization/order.
  if (!function_exists('return_bytes')) {
    // https://www.php.net/ini_get
    function return_bytes($val) {
      $val = trim($val);
      $last = strtolower($val[strlen($val) - 1]);
      switch ($last) {
        case 'g':
          $val *= 1024;
        case 'm':
          $val *= 1024;
        case 'k':
          $val *= 1024;
      }

      return $val;
    }
  }

  $memory_limit = return_bytes(ini_get('memory_limit'));
  if ($memory_limit < (256 * 1024 * 1024)) {
    ini_set('memory_limit', '512M');
  }
}
