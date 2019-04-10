# Drupal 8 at UNL (Project Herbie)

## Requirements

See [Drupal 8 System Requirements](https://www.drupal.org/docs/8/system-requirements/)

While it is possible to run Drupal on a variety of web servers, database servers, etc., the officially supported configuration at UNL is as follows:

- Linux (any modern, supported distribution)
- PHP 7.0 or greater
- Apache 2.4.6 or greater
- MariaDB 5.5.60 or greater

Composer, PHP's dependency manager, is necessary to install this project. See [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
It may be necessary to replace `composer` with `php composer.phar` (or similar).

## Installation

Navigate to the project root and install the project:

```
composer install
```

### Install the UNLedu Web Framework

There are two methods to install the UNLedu Web Framework:

1. automated
2. manual

#### Automated

The unl/wdntemplates package is already downloaded to /vendor/unl/wdntemplates/. Run the following command:

```
composer install-wdn
```

This command will create a symlink of /vendor/unl/wdntemplates/wdn at web/wdn.

The wdntemplates package is a Node.js project that uses Grunt. This command will also install the Node.js project and run the default Grunt task.

To receive upstream updates, navigate to /vendor/wdn/ and run `git pull`.

#### Manual

Download the [UNLedu Web Framework sync set](https://wdn.unl.edu/downloads/wdn_includes.zip) to `web/wdn`

### Install Drupal

See [Installing Drupal 8](https://www.drupal.org/docs/8/install)

When asked to select an Installation Profile, select "Use existing configuration"
