# Drupal 9 at UNL (Project Herbie)

## Requirements

See [Drupal System Requirements](https://www.drupal.org/docs/system-requirements)

While it is possible to run Drupal on a variety of web servers, database servers, etc., the officially supported configuration at UNL is as follows:

- Linux (any modern, supported distribution)
- PHP 7.4 or greater
- Apache 2.4 or greater
- MariaDB 10.6 or greater

Latest verified working configuration:

- PHP 7.4.28
- Apache 2.4.48
- MariaDB 10.7.3

Composer, PHP's dependency manager, is necessary to install this project. See [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
It may be necessary to replace `composer` with `php composer.phar` (or similar).

## Installation

Navigate to the project root and install the project:

```
composer install
```

## Install the UNLedu Web Framework

The [unl_five](https://github.com/unlcms/unl_five) theme requires the [UNLedu Web Framework](https://github.com/unl/wdntemplates)

There are two methods to install the UNLedu Web Framework:

1. automated
2. manual

### Automated

The unl/wdntemplates package is already downloaded to /vendor/unl/wdntemplates. Run the following command:

```
composer install-wdn
```

This command will create a symlink of /vendor/unl/wdntemplates/wdn at /web/wdn.

The wdntemplates package is a Node.js project that uses Grunt. This command will also install the Node.js project and run the default Grunt task.

To receive upstream updates, navigate to /vendor/wdn and run `git pull`.

### Manual

Download the [UNLedu Web Framework sync set](https://wdn.unl.edu/downloads/wdn_includes.zip) to `/web/wdn`

## Install Drupal

Copy `web/sites/default/default.settings.php` to `web/sites/default/settings.php`

Navigate to `http://example.unl.edu/project-herbie/web` in your browser.

See [Installing Drupal](https://www.drupal.org/docs/installing-drupal)

When asked to select an Installation Profile, select "Use existing configuration".

## Config Split

This project uses Config Split to manage configuration among production, stage, and development. Certain modules, such as Twig Xdebug and Config Inspector are only enabled on development.

In the development config split, a number of settings are enabled, disabled, or modified: Caching is disabled; Twig caching is disabled and Twig autoloading is enabled; debug cacheability headers are enabled; CSS and JS aggregation is disabled; and file permission hardening is disabled.  See /profiles/herbie/includes/settings.php.inc for more details. These settings can be overridden in settings.local.php.

## (Optional) Running multisite

The unl_multisite module allows additional sites to be run from subdirectories such as http://example.unl.edu/site2. This is not required to run the base site installation.

```
cp web/sites/example.sites.php web/sites/sites.php
cp web/.htaccess-subsite-map.txt.sample web/.htaccess-subsite-map.txt
```

Add the following line to your Apache's configuration file (httpd.conf) where <DRUPAL_ROOT> is the file system path to the Drupal web root. Restart Apache afterward.

```
RewriteMap drupal_unl_multisite txt:<DRUPAL_ROOT>/.htaccess-subsite-map.txt
```

Set up a cron job on the server to execute `php web/modules/contrib/unl_multisite/cron.php` on a regular basis.

Enable the UNL Multisite module on the default site. It should only be enabled on the default site.


## D9 todo:

This patch needs updated for 9.4: https://www.drupal.org/node/3060292

                "3060292 - Drupal\\media\\Entity\\Media::prepareSave should convert URL object metadata to string before saving" : "patches/drupal_media-resource_convert_url_object_to_string-3060292-20-D9.patch",

Update Twig UI Templates to 1.0.1
