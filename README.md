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

Then navigate to `http://example.unl.edu/project-herbie/web` in your browser and follow the installation instructions, selecting "Use existing configuration" as the installation profile. 
