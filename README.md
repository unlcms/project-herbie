# Drupal 9 at UNL (Project Herbie)

A multisite installation hosted at http://cms.unl.edu/ developed by the [Digital Experience Group](https://ucomm.unl.edu/) 
and supported by DXG and ITS.

## Requirements

See [Drupal System Requirements](https://www.drupal.org/docs/system-requirements)

While it is possible to run Drupal on a variety of web servers, database servers, etc., 
the officially supported configuration at UNL is as follows:

- Linux (any modern, supported distribution)
- PHP 7.4 or greater
- Apache 2.4 or greater
- MariaDB 10.6 or greater

Latest verified working configuration:

- PHP 7.4.28
- Apache 2.4.48
- MariaDB 10.7.3

Composer, PHP's dependency manager, is necessary to install this project. 
See [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
It may be necessary to replace `composer` with `php composer.phar` (or similar).

### DDEV Support

See the _DDEV-README.md_ file in this project.

## Installation

Navigate to the project root and install the project:

```
composer install
```

### Install the UNLedu Web Framework

The [unl_five](https://github.com/unlcms/unl_five) theme requires the [UNLedu Web Framework](https://github.com/unl/wdntemplates).

There are two methods to install the UNLedu Web Framework:

1. Automated
2. Manual

#### Automated

The unl/wdntemplates package is already downloaded to `/vendor/unl/wdntemplates`. Run the following command:

```
composer install-wdn
```

This command will create a symlink of `/vendor/unl/wdntemplates/wdn` at `/web/wdn`.

The wdntemplates package is a Node.js project that uses Grunt. This command will also install 
the Node.js project and run the default Grunt task.

To receive upstream updates, navigate to `/vendor/wdn` and run `git pull`.

#### Manual

Download the [UNLedu Web Framework sync set](https://wdn.unl.edu/downloads/wdn_includes.zip) to `/web/wdn`.

### Install Drupal

```
cp web/sites/default/default.settings.php web/sites/default/settings.php
```

Navigate to _http://example.unl.edu/project-herbie/web/_ (or set up a virtual host, _cms-local.unl.edu_ is the recommended name) in your browser. 
(See [Installing Drupal](https://www.drupal.org/docs/installing-drupal))

When asked to select an Installation Profile, select _Use existing configuration_. 

Decide if you want to run a multisite installation.  (See "Running Multisite" below.) 

#### Local Settings

Create a file at `web/sites/default/settings.php` and add the LDAP password:

```php
<?php

$config['unl_user.settings']['password'] = 'PASSWORD_GOES_HERE';
```

## Upgrading Drupal Core (or a module)

Run this on a development site and commit composer.json, composer.lock, and any changes to `config/sync`.
The process is the same for a module, just change the project in the first composer command.
```
composer update "drupal/core-*" --with-all-dependencies
drush updatedb
drush cache:rebuild
drush config:export
```

Run on a deployment after updating code base:
```
composer install
```

Run on all sites:
```
drush updatedb
drush cache:rebuild
```

## Configuration Management

This project uses Drupal 9 Configuration Management to store the present/base/main configuration of a new site.

After making changes, use `drush config:export` to export config to `config/sync` and commit. 
This config is used during a site installation to instantiate a site.

### Multisite-wide Config Changes

Important: **_Never use drush config:import_**. This multisite environment is not a single site that can have all of its 
configuration captured in config/sync. Each site may have custom content types and other configuration that would be
overwritten/deleted by a config import. As noted previously, `config/sync` is only used as a 
"distribution install profile" during the creation of a new site.

Once again, with feeling, **_Never use drush config:import_**.

Options to deploy a configuration setting across sites:

1. First check if the setting is part of a Feature. (Is the _State_ clean on `admin/config/development/features`?)
If not, update the Feature. (See "Features" section below.) 
2. Consider creating a new Feature for a package of configuration. 
3. Use `drush config:set` to change a config value. An example is `drush config:set unl_user.settings username_format myunl`. 
4. Alternatively, a non-editable config value (one that end-users shouldn't be able to change) can be set in `/profiles/herbie/includes/settings.php.inc` and committed.

## Config Split

This project uses Config Split to manage configuration among production, stage, and development. Certain modules, 
such as Twig Xdebug and Config Inspector are only enabled on development.

In the development config split, a number of settings are enabled, disabled, or modified: Caching is disabled; 
Twig caching is disabled and Twig autoloading is enabled; debug cacheability headers are enabled; 
CSS and JS aggregation is disabled; and file permission hardening is disabled.  See 
/profiles/herbie/includes/settings.php.inc for more details. These settings can be overridden in settings.local.php.

## Features

Are written to `web/modules/custom/features` and part of the `herbie` feature bundle.

### Updating a Feature

In a development site, make the changes, bump the **Version** on `admin/config/development/features/edit/herbie_FEATURE` and click _Write_. Then commit changes.

### Updating Multisites with the Feature update

Deploy changes. Then run `drush fim herbie_FEATURE` on all sites.

## Running Multisite

The [unl_multisite module](https://github.com/unlcms/unl_multisite) provides a web interface to run 
a [Drupal multisite](https://www.drupal.org/docs/multisite-drupal) setup so additional sites can exist in 
subdirectories such as http://example.unl.edu/site2. (This is not required to run the base site installation as a single site.)

Much of the setup that is detailed in the unl_multisite module's README file has been done in this project with 
Composer scripts. The following is what needs to be done manually.

1. Create these files from the provided samples:

```
cp web/sites/example.sites.php web/sites/sites.php
cp web/.htaccess-subsite-map.txt.sample web/.htaccess-subsite-map.txt
```

2. Add the following line to your Apache's configuration file (httpd.conf) where <DRUPAL_ROOT> is the file system path to the Drupal web root. Restart Apache afterward.

```
RewriteMap drupal_unl_multisite txt:<DRUPAL_ROOT>/.htaccess-subsite-map.txt
```

3. Set up a cron job on the server to execute `php web/modules/contrib/unl_multisite/cron.php` on a regular basis.

4. Ensure the MySQL/MariaDB database user for the default site has privileges to create new databases.

5. Enable the UNL Multisite module on the default site. It should only be enabled on the default site.

### Multisite Troubleshooting

- Drush and MariaDB 10.7+ don't work with a database port set if the host is localhost. If you are using localhost for the host, clear the port setting in settings.php.

