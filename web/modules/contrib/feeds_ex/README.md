Feeds extensible parsers
========================

A set of extensible parsers for Feeds.
http://drupal.org/project/feeds_ex

Provided parsers
================
- XPath XML & HTML
- JSONPath JSON & JSON lines parser (requires a JSONPath library)
- JMESPath JSON & JSON lines parser (requires the JMESPath library)
- QueryPath XML & HTML (requires the QueryPath library)

Requirements
============

- Feeds
  http://drupal.org/project/feeds

Installation
============

- Download and enable just like a normal module.

Some parsers of this module require additional libraries. If you installed this
module through Composer, you already have these libraries. If you've not, there
are two ways to get these libraries:

1. Require them with Composer.
2. Install them manually, using the Ludwig module.


Require libraries with Composer
-------------------------------
If you installed this module through Composer using the command
`composer require drupal/feeds_ex` then there's nothing you need to do. The
required libraries are already installed. Else, read on.

### JSONPath
The JSONPath parsers require a JSONPath library. To require it with Composer:

$ composer require softcreatr/jsonpath:^0.5 || ^0.7

The source code for this library can be found at:
https://github.com/SoftCreatR/JSONPath

### JMESPath
To use the JMESPath parsers, you will need the JMESPath library. To require it
with Composer:

$ composer require mtdowling/jmespath.php:^2.0

The source code for this library can be found at:
https://github.com/jmespath/jmespath.php

### QueryPath
To use the QueryPath parsers, you will need the QueryPath library. To require it
with Composer:

$ composer require arthurkushman/query-path:^3.0

The source code for this library can be found at:
https://github.com/technosophos/querypath


Manual install, using the Ludwig module
---------------------------------------
Composer is the recommended way to install and maintain a site. Site
administrators using Ludwig need to be careful when combining modules that
depend on external libraries, since there are no safeguards against incompatible
library versions or overlapping requirements.

Steps:

1. Download and install the Ludwig module.
   https://www.drupal.org/project/ludwig

2. Download and install Feeds and Feeds extensible parsers.

3. Ludwig generates a listing of libraries required by those modules. The
   Packages page at admin/reports/packages provides a download link for each
   missing library along with the paths where they should be placed.
