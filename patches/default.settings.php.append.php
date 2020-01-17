/*
 * Load Herbie profile configuration.
 */
include $app_root . '/profiles/herbie/includes/settings.php.inc';

/*
 * Load local settings.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
