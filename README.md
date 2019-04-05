# Drupal 8 at UNL (Project Herbie)

## Installation

First you need the [UNLedu Web Framework sync set](https://wdn.unl.edu/downloads/wdn_includes.zip). This should reside at `web/wdn`

You also need to [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
You might need to replace `composer` with `php composer.phar` (or similar) 
for your setup.

After that you can create the project:

```
composer install
```

Copy the sample .htaccess file to its usable name:

```
cp web/.htaccess.sample web/.htaccess
```

Then navigate to `http://example.unl.edu/project-herbie/web` in your browser and follow the installation instructions, selecting "Use existing configuration" as the installation profile. 


## Running multisite

The unl_multisite module allows additional sites to be run from subdirectories such as http://example.unl.edu/site2. This is not required to run the base site installation.

```
cp web/sites/example.sites.php web/sites/sites.php
cp web/.htaccess-subsite-map.txt.sample web/.htaccess-subsite-map.txt
```

Add the following line to your Apache's configuration file (httpd.conf) where <DRUPAL_ROOT> is the file system path to the Drupal web root. Restart Apache afterward.

```
  RewriteMap drupal_unl_multisite txt:<DRUPAL_ROOT>/.htaccess-subsite-map.txt
```


Enable the UNL Multisite module on the main site. It should only be enabled on the main site.
