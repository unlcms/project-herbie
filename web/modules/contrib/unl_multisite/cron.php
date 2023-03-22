<?php
/**
 * 'installed' database number codes (also seen in unl_multisite/unl_site_creation.php)
 *  0: 'Scheduled for creation.'
 *  1: 'Currently being created.'
 *  2: 'In production.'
 *  3: 'Scheduled for removal.'
 *  4: 'Currently being removed.'
 *  5: 'Failure/Unknown.'
 *  6: 'Scheduled for site update.'
 */

use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI != 'cli') {
  echo 'This script must be run from the shell!';
  exit;
}

chdir(dirname(__FILE__) . '/../../..');

// Bootstrap.
$autoloader = require 'autoload.php';
require_once  'core/includes/bootstrap.inc';
$request = Request::createFromGlobals();
Settings::initialize(dirname(dirname(__DIR__)), DrupalKernel::findSitePath($request), $autoloader);
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();

//unl_edit_sites();
unl_remove_aliases();
unl_remove_sites();
unl_add_sites();
unl_add_aliases();

function unl_add_sites() {
  $database_connection = \Drupal::service('database');

  $query = $database_connection->query('SELECT * FROM {unl_sites} WHERE installed=0');

  while ($row = $query->fetchAssoc()) {
    $database_connection->update('unl_sites')
      ->fields(array('installed' => 1))
      ->condition('site_id', $row['site_id'])
      ->execute();
    try {
      unl_add_site($row['site_path'], $row['uri'], $row['site_id']);
      $database_connection->update('unl_sites')
        ->fields(array('installed' => 2))
        ->condition('site_id', $row['site_id'])
        ->execute();
    } catch (Exception $e) {
      \Drupal::logger('unl cron')->error($e->getMessage(), array());
      $database_connection->update('unl_sites')
        ->fields(array('installed' => 5))
        ->condition('site_id', $row['site_id'])
        ->execute();
    }
  }
}

function unl_remove_sites() {
  $database_connection = \Drupal::service('database');

  $query = $database_connection->query('SELECT * FROM {unl_sites} WHERE installed=3');
  while ($row = $query->fetchAssoc()) {
    $database_connection->update('unl_sites')
      ->fields(array('installed' => 4))
      ->condition('site_id', $row['site_id'])
      ->execute();
    try {
      unl_remove_site($row['site_path'], $row['uri'], $row['site_id']);
      $database_connection->delete('unl_sites')
        ->condition('site_id', $row['site_id'])
        ->execute();
    } catch (Exception $e) {
      \Drupal::logger('unl cron')->error($e->getMessage(), array());
      $database_connection->update('unl_sites')
        ->fields(array('installed' => 5))
        ->condition('site_id', $row['site_id'])
        ->execute();
    }
  }
}

function unl_edit_sites() {
  $database_connection = \Drupal::service('database');

  $query = $database_connection->query('SELECT * FROM {unl_sites} WHERE installed=6');
  while ($row = $query->fetchAssoc()) {
    try {
      $alias = $database_connection->select('unl_sites_aliases')
        ->fields('unl_sites_aliases', array('site_alias_id', 'site_id', 'base_uri', 'path'))
        ->condition('installed', 6)
        ->condition('site_id', $row['site_id'])
        ->execute()
        ->fetchAssoc();

      $database_connection->update('unl_sites')
        ->fields(array('site_path' => $alias['path']))
        ->condition('site_id', $row['site_id'])
        ->execute();

      $database_connection->update('unl_sites_aliases')
        ->fields(array('path' => $row['site_path']))
        ->condition('site_id', $row['site_id'])
        ->condition('installed', 6)
        ->execute();

      // Original sites subdir
      $sites_subdir = unl_get_sites_subdir($row['uri']);
      $sites_subdir = \Drupal::root() . '/sites/' . $sites_subdir;
      $sites_subdir = realpath($sites_subdir);
      // New sites subdir
      $new_sites_subdir = unl_get_sites_subdir(strtolower($new_uri));
      $new_sites_subdir = \Drupal::root() . '/sites/' . $new_sites_subdir;
      // mv original to new
      shell_exec('chmod -R u+w ' . escapeshellarg($sites_subdir));
      $command = 'mv ' . escapeshellarg($sites_subdir) . ' ' . escapeshellarg($new_sites_subdir);
      shell_exec($command);

      // Recreate all existing aliases so that they point to the new URI.
      $existingAliases = $database_connection->select('unl_sites_aliases', 'a')
        ->condition('site_id', $row['site_id'])
        ->condition('installed', 2)
        ->fields('a', array('site_alias_id', 'base_uri', 'path'))
        ->execute()
        ->fetchAll();
      foreach ($existingAliases as $existingAlias) {
          unl_remove_alias($existingAlias->site_alias_id);
          unl_add_alias($new_uri, $existingAlias->base_uri, $existingAlias->path, $existingAlias->site_alias_id);
      }

      // Add the old location as a new alias.
      unl_add_alias($new_uri, $alias['base_uri'], $row['site_path'], $alias['site_alias_id']);

      $database_connection->update('unl_sites')
        ->fields(array('installed' => 2))
        ->condition('site_id', $row['site_id'])
        ->execute();
      $database_connection->update('unl_sites_aliases')
        ->fields(array('installed' => 2))
        ->condition('site_id', $row['site_id'])
        ->condition('installed', 6)
        ->execute();
    } catch (Exception $e) {
      \Drupal::logger('unl cron')->error($e->getMessage(), array());
      $database_connection->update('unl_sites')
        ->fields(array('installed' => 5))
        ->condition('site_id', $row['site_id'])
        ->execute();
      $database_connection->update('unl_sites_aliases')
        ->fields(array('installed' => 5))
        ->condition('site_id', $row['site_id'])
        ->condition('installed', 6)
        ->execute();
    }
  }
}

function unl_add_aliases() {
  $database_connection = \Drupal::service('database');

  $query = $database_connection->select('unl_sites_aliases', 'a');
  $query->join('unl_sites', 's', 's.site_id = a.site_id');
  $query->fields('s', array('uri'));
  $query->fields('a', array('site_alias_id', 'base_uri', 'path'));
  $query->condition('a.installed', 0);
  $results = $query->execute()->fetchAll();

  foreach ($results as $row) {
    $database_connection->update('unl_sites_aliases')
      ->fields(array('installed' => 1))
      ->condition('site_alias_id', $row->site_alias_id)
      ->execute();
    try {
      unl_add_alias($row->uri, $row->base_uri, $row->path, $row->site_alias_id);
      $database_connection->update('unl_sites_aliases')
        ->fields(array('installed' => 2))
        ->condition('site_alias_id', $row->site_alias_id)
        ->execute();
    } catch (Exception $e) {
      \Drupal::logger('unl cron')->error($e->getMessage(), array());
      $database_connection->update('unl_sites_aliases')
        ->fields(array('installed' => 5))
        ->condition('site_alias_id', $row->site_alias_id)
        ->execute();
    }
  }
}

function unl_remove_aliases() {
  $database_connection = \Drupal::service('database');

  $query = $database_connection->select('unl_sites_aliases', 'a');
  $query->fields('a', array('site_alias_id', 'base_uri', 'path'));
  $query->condition('a.installed', 3);
  $results = $query->execute()->fetchAll();

  foreach ($results as $row) {
    $database_connection->update('unl_sites_aliases')
      ->fields(array('installed' => 4))
      ->condition('site_alias_id', $row->site_alias_id)
      ->execute();
    try {
      unl_remove_alias($row->site_alias_id);
      $database_connection->delete('unl_sites_aliases')
        ->condition('site_alias_id', $row->site_alias_id)
        ->execute();
    } catch (Exception $e) {
      \Drupal::logger('unl cron')->error($e->getMessage(), array());
      $database_connection->update('unl_sites_aliases')
        ->fields(array('installed' => 5))
        ->condition('site_alias_id', $row->site_alias_id)
        ->execute();
    }
  }
}

function unl_add_site($site_path, $uri, $site_id) {
  if (substr($site_path, 0, 1) == '/') {
    $site_path = substr($site_path, 1);
  }
  if (substr($site_path, -1) == '/') {
    $site_path = substr($site_path, 0, -1);
  }

  $sites_subdir = unl_get_sites_subdir($uri);

  //Create a fresh site
  $connection_info = Database::getConnectionInfo();
  $database = $connection_info['default'];

  $db_url = $database['driver']
    . '://' . $database['username']
    . ':'   . $database['password']
    . '@'   . $database['host']
    . ($database['port'] ? ':' . $database['port'] : '')
    . '/project-herbie-'   . $site_id
  ;

  // Drush 8 doesn't like single quotes around option values so escapeshellarg doesn't work.
  $php_path = escapeshellarg($_SERVER['_']);
  $drush_path = dirname(DRUPAL_ROOT) . '/vendor/drush/drush/drush';
  $uri = escapeshellcmd($uri);
  $sites_subdir = escapeshellcmd($sites_subdir);
  $db_url = escapeshellcmd($db_url);

  // Drush site-install can't create a database with special characters, like
  // hyphens, because it doesn't quote the name. However, it does quote the name
  // with the "sql:create" command. Creating and then installing in two steps
  // is a workaround until the following issue is addressed:
  // https://github.com/drush-ops/drush/issues/4203
  $command = "$drush_path -y sql:create --db-url=$db_url 2>&1";
  $result = shell_exec($command);
  echo $result;
  if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
    throw new Exception('Error while running drush sql:create.');
  }

  // Site installation.
  $command = "$drush_path -y --uri=$uri site-install --existing-config --sites-subdir=$sites_subdir --db-url=$db_url 2>&1";
  $result = shell_exec($command);
  echo $result;
  if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
    throw new Exception('Error while running drush site-install.');
  }

  unl_add_site_to_htaccess($site_id, $site_path, FALSE);

  // Set files directory permissions on the new site.
  // @todo Change owner to the Apache user instead.
  $command = "$drush_path --uri=$uri drupal-directory files 2>&1";
  $result = shell_exec($command);
  echo $result;
  if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
    throw new Exception('Error while running drush drupal-directory.');
  }
  $command = "chmod -R 777 $result 2>&1";
  $result = shell_exec($command);
  echo $result;

  // Add the default site's administrators to the new site.
  $command = "$drush_path users:list --roles=administrator --format=php --fields=name,mail 2>&1";
  $result = shell_exec($command);
  echo $result;
  if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
    throw new Exception('Error while running drush users:list.');
  }
  foreach(unserialize($result) as $admin) {
    $name = $admin['name'];
    $mail = $admin['mail'];
    $command = "$drush_path --uri=$uri user:create $name --mail='$mail' 2>&1";
    $result = shell_exec($command);
    echo $result;
    if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
      throw new Exception('Error while running drush user:create.');
    }
    $command = "$drush_path --uri=$uri user:role:add 'administrator' $name 2>&1";
    $result = shell_exec($command);
    echo $result;
    if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
      throw new Exception('Error while running drush user:role:add.');
    }
  }

  // On the new site the users created need added to the cas module table,
  // "authmap", where the "Allow user to log in via CAS" setting is stored.
  $command = "$drush_path --uri=$uri users:list --roles=administrator --format=php --fields=name,mail 2>&1";
  $result = shell_exec($command);
  echo $result;
  if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
    throw new Exception('Error while running drush users:list on new site.');
  }
  foreach(unserialize($result) as $key => $admin) {
    $name = $admin['name'];
    $command = "$drush_path --uri=$uri sql-query 'INSERT INTO authmap (uid, provider, authname) VALUES ($key, \"cas\", \"$name\")' 2>&1";
    $result = shell_exec($command);
    echo $result;
    if (stripos($result, 'Drush command terminated abnormally') !== FALSE) {
      throw new Exception('Error while running drush sql-query to insert into authmap.');
    }
  }

}

function unl_remove_site($site_path, $uri, $site_id) {
  $sites_subdir = unl_get_sites_subdir($uri);
  $sites_subdir = \Drupal::root() . '/sites/' . $sites_subdir;
  $sites_subdir = realpath($sites_subdir);

  // A couple checks to make sure we aren't deleting something we shouldn't be.
  if (substr($sites_subdir, 0, strlen(\Drupal::root() . '/sites/')) != \Drupal::root() . '/sites/') {
    throw new Exception('Attempt to delete a directory outside \Drupal::root() was aborted.');
  }

  if (strlen($sites_subdir) <= strlen(\Drupal::root() . '/sites/')) {
    throw new Exception('Attempt to delete a directory outside \Drupal::root() was aborted.');
  }

  // Drop all the tables in the database.
  $connection_info = Database::getConnectionInfo();
  $database = $connection_info['default'];
  $database['database'] = 'project-herbie-' . $site_id;
  $command = "mysqladmin -h {$database['host']} -u {$database['username']} -p{$database['password']} -f drop {$database['database']} 2>&1";
  $result = shell_exec($command);
  echo $result;

  // Do our best to remove the sites
  shell_exec('chmod -R u+w ' . escapeshellarg($sites_subdir));
  shell_exec('rm -rf ' . escapeshellarg($sites_subdir));

  // Remove the rewrite rules from .htaccess for this site.
  unl_remove_site_from_htaccess($site_id, FALSE);

  // If we were using memcache, flush its cache so new sites don't have stale data.
  if (class_exists('MemCacheDrupal', FALSE)) {
    dmemcache_flush();
  }
}

function unl_add_alias($site_uri, $base_uri, $path, $alias_id) {
  $alias_uri = $base_uri . $path;
  $real_config_dir = unl_get_sites_subdir($site_uri);
  $alias_config_dir = unl_get_sites_subdir($alias_uri, FALSE);

  unl_add_alias_to_sites_php($alias_config_dir, $real_config_dir, $alias_id);
  if ($path) {
    unl_add_site_to_htaccess($alias_id, $path, TRUE);
  }
}

function unl_remove_alias($alias_id) {
  unl_remove_alias_from_sites_php($alias_id);
  unl_remove_site_from_htaccess($alias_id, TRUE);
}

function unl_add_alias_to_sites_php($alias_site_dir, $real_site_dir, $alias_id) {
  unl_require_writable(\Drupal::root() . '/sites/sites.php');

  $stub_token = '# %UNL_CREATION_TOOL_STUB%';
  $sites_php = file_get_contents(\Drupal::root() . '/sites/sites.php');
  $stub_pos = strpos($sites_php, $stub_token);
  if ($stub_pos === FALSE) {
    throw new Exception('Unable to find stub alias entry in sites.php.');
  }
  $new_sites_php = substr($sites_php, 0, $stub_pos)
                 . "# %UNL_START_ALIAS_ID_{$alias_id}%\n"
                 . "\$sites['$alias_site_dir'] = '$real_site_dir';\n"
                 . "# %UNL_END_ALIAS_ID_{$alias_id}%\n\n"
                 . $stub_token
                 . substr($sites_php, $stub_pos + strlen($stub_token))
                 ;
  _unl_file_put_contents_atomic(\Drupal::root() . '/sites/sites.php', $new_sites_php);
}

function unl_remove_alias_from_sites_php($alias_id) {
  unl_require_writable(\Drupal::root() . '/sites/sites.php');

  $sites_php = file_get_contents(\Drupal::root() . '/sites/sites.php');
  $site_start_token = "\n# %UNL_START_ALIAS_ID_{$alias_id}%";
  $site_end_token = "# %UNL_END_ALIAS_ID_{$alias_id}%\n";

  $start_pos = strpos($sites_php, $site_start_token);
  $end_pos = strpos($sites_php, $site_end_token);

  // If its already gone, we don't need to do anything.
  if ($start_pos === FALSE || $end_pos === FALSE) {
    return;
  }
  $new_sites_php = substr($sites_php, 0, $start_pos)
                 . substr($sites_php, $end_pos + strlen($site_end_token))
                 ;
  _unl_file_put_contents_atomic(\Drupal::root() . '/sites/sites.php', $new_sites_php);
}

function unl_require_writable($path) {
  if (!is_writable($path)) {
    throw new Exception('The file "' . $path . '" needs to be writable and is not.');
  }
}

function unl_add_site_to_htaccess($site_id, $site_path, $is_alias) {
  if ($is_alias) {
    $site_or_alias = 'ALIAS';
  }
  else {
    $site_or_alias = 'SITE';
  }

  if (substr($site_path, -1) != '/') {
    $site_path .= '/';
  }

  unl_require_writable(DRUPAL_ROOT . '/.htaccess-subsite-map.txt');

  $stub_token = '# %UNL_CREATION_TOOL_STUB%';
  $htaccess = file_get_contents(DRUPAL_ROOT . '/.htaccess-subsite-map.txt');
  $stub_pos = strpos($htaccess, $stub_token);
  if ($stub_pos === FALSE) {
    throw new Exception('Unable to find stub site entry in .htaccess-subsite-map.txt.');
  }
  $new_htaccess = substr($htaccess, 0, $stub_pos)
    . "# %UNL_START_{$site_or_alias}_ID_{$site_id}%\n";
  foreach (array('core/assets', 'core/misc', 'core/modules', 'core/themes', 'modules', 'sites', 'themes') as $drupal_dir) {
    $new_htaccess .=  "$site_path$drupal_dir $drupal_dir\n";
  }
  $new_htaccess .= "# %UNL_END_{$site_or_alias}_ID_{$site_id}%\n\n"
    . $stub_token
    . substr($htaccess, $stub_pos + strlen($stub_token));

  _unl_file_put_contents_atomic(DRUPAL_ROOT . '/.htaccess-subsite-map.txt', $new_htaccess);
}

function unl_remove_site_from_htaccess($site_id, $is_alias) {
  if ($is_alias) {
    $site_or_alias = 'ALIAS';
  }
  else {
    $site_or_alias = 'SITE';
  }

  unl_require_writable(DRUPAL_ROOT . '/.htaccess-subsite-map.txt');

  $htaccess = file_get_contents(DRUPAL_ROOT . '/.htaccess-subsite-map.txt');
  $site_start_token = "\n# %UNL_START_{$site_or_alias}_ID_{$site_id}%";
  $site_end_token = "# %UNL_END_{$site_or_alias}_ID_{$site_id}%\n";

  $start_pos = strpos($htaccess, $site_start_token);
  $end_pos = strpos($htaccess, $site_end_token);

  // If its already gone, we don't need to do anything.
  if ($start_pos === FALSE || $end_pos === FALSE) {
    return;
  }
  $new_htaccess = substr($htaccess, 0, $start_pos)
    . substr($htaccess, $end_pos + strlen($site_end_token))
  ;

  _unl_file_put_contents_atomic(DRUPAL_ROOT . '/.htaccess-subsite-map.txt', $new_htaccess);
}

/**
 * A drop-in replacement for file_put_contents that will atomically put the new file into place.
 * This additionally requires you to have write access to the directory that will contain the file.
 * @see file_put_contents
 */
function _unl_file_put_contents_atomic($filename, $data, $flags = 0, $context = NULL) {
  // Create a temporary file with a simalar name in the destination directory.
  $tempfile = tempnam(dirname($filename), basename($filename) . '_');
  if ($tempfile === FALSE) {
    return FALSE;
  }
  // Fix the permissions on the file since they will be 0600.
  if (file_exists($filename)) {
    $stat = stat($filename);
    chmod($tempfile, $stat['mode']);
  } else {
    chmod($tempfile, 0666 & ~umask());
  }

  // Do the actual file_put contents
  $bytes = file_put_contents($tempfile, $data, $flags, $context);
  if ($bytes === FALSE) {
    unlink($tempfile);
    return FALSE;
  }

  // Move the new file into place atomically.
  if (!rename($tempfile, $filename)) {
    unlink($tempfile);
    return FALSE;
  }

  return $bytes;
}

/**
 * Given a URI, will return the name of the directory for that site in the sites directory.
 */
function unl_get_sites_subdir($uri, $trim_subdomain = TRUE) {
  $path_parts = parse_url($uri);
  if ($trim_subdomain && substr($path_parts['host'], -7) == 'unl.edu') {
    $path_parts['host'] = 'unl.edu';
  }
  $sites_subdir = $path_parts['host'] . $path_parts['path'];
  $sites_subdir = strtr($sites_subdir, array('/' => '.'));
  while (substr($sites_subdir, 0, 1) == '.') {
    $sites_subdir = substr($sites_subdir, 1);
  }
  while (substr($sites_subdir, -1) == '.') {
    $sites_subdir = substr($sites_subdir, 0, -1);
  }
  return $sites_subdir;
}
