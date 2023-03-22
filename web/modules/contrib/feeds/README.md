Feeds
=====
## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Maintainers

## INTRODUCTION

Feeds is the module for importing or aggregating data into nodes, users,
taxonomy terms and other content entities. Data can be imported from various
formats, such as CSV, JSON*, XML* and RSS feeds.

First, site builders configure the import parameters by creating a feed type in
the UI and configure how the source data maps to the fields in Drupal.
Content editors can then import their source in the UI by creating a feed.
Multiple feeds can exist per feed type, allowing you to import from multiple
sources or allowing multiple editors to update their own content.

You can configure Feeds to update content periodically or to import content just
once. Details of how many items got imported can be found in the feeds log
reports.

* For importing from JSON and XML, you need the extension module "Feeds
Extensible Parsers".

### Features

 * Create nodes, users, taxonomy terms or other content entities from import
 * One-off imports and periodic aggregation of content
 * Import or aggregate RSS/Atom feeds
 * Import or aggregate CSV files
 * Import or aggregate OPML files
 * PubSubHubbub support
 * Extensible to import any other kind of content
 * Granular mapping of input elements to Drupal content elements
 * Modify data before import (requires the Feeds Tamper module)
 * Exportable configurations
 * Batched import for large files

### Resources

 * For a full description of the module, visit the project page:
   <https://www.drupal.org/project/feeds>

 * To submit bug reports and feature suggestions, or track changes:
   <https://www.drupal.org/project/issues/feeds>

 * For complete usage and developer documentation:
   <https://www.drupal.org/docs/8/modules/feeds>


## REQUIREMENTS

This module requires no modules outside of Drupal core.


## INSTALLATION

 * Install as you would normally install a contributed Drupal module. Visit
   <https://www.drupal.org/node/1897420> for further information.


## CONFIGURATION

After installation, creating a new import feed in Drupal 8 requires three steps:

1. Create a "Feed type" from Administration > Structure > Feeds
   (/admin/structure/feeds) that describes import parameters, such as source
   type (for example, CSV), frequency, etc.
2. Map "sources" from the import data structure to "targets" of the entity
   (content, user, taxonomy, etc.) that you are importing to
   Administration > Structure > Feeds > {created feed} > Mapping
   (/admin/structure/feeds/*/mapping).
3. Create a "Feed" of the "Feed type" you want (/feed/add), select source
   (file or url), and import actual data.

For a complete walkthrough of configuration options and process, visit:
<https://drupal.org/docs/8/modules/feeds/creating-and-editing-import-feeds>


## MAINTAINERS

### Current maintainers:

 * Youri van Koppen [MegaChriz](https://www.drupal.org/u/megachriz)

### Historical maintainers:

 * Chris Leppanen [twistor](https://www.drupal.org/u/twistor)
 * [tristanoneil](https://www.drupal.org/user/340659)
 * Franz Glauber Vanderlinde [franz](https://www.drupal.org/u/franz)
 * Frank Febbraro [febbraro](https://www.drupal.org/u/febbraro)
