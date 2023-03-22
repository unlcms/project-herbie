# UNL Multisite Module for Drupal 9

Provides a web interface to run a [Drupal multisite](https://www.drupal.org/docs/multisite-drupal) setup so additional sites can exist in subdirectories such as http://example.unl.edu/site2.

## Installation

1. Insert the following line into index.php after $request is initialized:

`require_once './modules/contrib/unl_multisite/bootstrap.inc';`


Your index.php file should look like:
```
$request = Request::createFromGlobals();
require_once './modules/contrib/unl_multisite/bootstrap.inc';
$response = $kernel->handle($request);
```

2. Copy .htaccess-subsite-map.txt.sample to the web root and rename to .htaccess-subsite-map.txt

3. Set the location of your config directory in sites/default/settings.php - [See Drupal.org for help](https://www.drupal.org/docs/8/configuration-management/changing-the-storage-location-of-the-sync-directory)

```
$settings['config_sync_directory'] = '../config/sync';
```

4. Copy sites/example.sites.php to sites/sites.php and add this to the end of the file:

```
  /**
   * Stub for the unl_multisite module to generate site aliases.
   */
  # THIS SECTION IS AUTOMATICALLY GENERATED
  # DO NOT EDIT!!!!

  # %UNL_CREATION_TOOL_STUB%

  # END OF AUTOMATICALLY GENERATED AREA
```


5. Add this to .htaccess at the web root (inside the <IfModule mod_rewrite.c> </IfModule> block).

```
  # START unl_multisite SECTION
  # Add the following line to your httpd.conf where <DRUPAL_ROOT> is the file system path to the Drupal web root.
  # RewriteMap drupal_unl_multisite txt:<DRUPAL_ROOT>/.htaccess-subsite-map.txt
  # Do not uncomment the previous line.
  RewriteRule .*/cron.php cron.php
  RewriteRule .*/update.php update.php
  RewriteRule ^(.*?/(core\/assets|core\/misc|core\/modules|core\/themes|modules|sites|themes))(.*) ${drupal_unl_multisite:$1|$1}$3 [DPI]

  RewriteCond ${drupal_unl_multisite://%{HTTP_HOST}%{REQUEST_URI}|NOT_FOUND} !^NOT_FOUND$
  RewriteRule (.*) ${drupal_unl_multisite://%{HTTP_HOST}%{REQUEST_URI}|$1} [R,L]
  # END unl_multisite SECTION
```


6. Add the following line to your Apache's configuration file (httpd.conf) where <DRUPAL_ROOT> is the file system path to the Drupal web root. Restart Apache afterward.

```
  RewriteMap drupal_unl_multisite txt:<DRUPAL_ROOT>/.htaccess-subsite-map.txt
```


7. Set up a cron job on the server to execute unl_multisite/cron.php on a regular basis.


8. Ensure the MYSQL database user for the default site has privileges to create new databases.

## Troubleshooting

- Drush and MariaDB 10.7+ don't work with a database port set if the host is localhost. If you are using localhost for the host, clear the port setting in settings.php.

