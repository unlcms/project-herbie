# Linkit Media Library

This module provides a button from the Link dialog within the WYSIWYG to insert
links to media items.

Upon link insertion, the 'target' attribute is automatically set to '_blank' to
open the linked document in a new window/tab.

## REQUIREMENTS

* Drupal 8 or 9
* [Linkit](https://www.drupal.org/project/linkit) module
  * Supported versions: 8.x-5.x and 6.x.x

## INSTALLATION

The module can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

The module when installed updates the default linkit profile to add a 
media matcher. If you would like to limit this, for example, to just the
document media type, update the default matcher for media:

admin/config/content/linkit/manage/default/matchers

This module depends on linkit to be enabled for the text format you are using.

For example for the full_html text format, you would go to

admin/config/content/formats/manage/full_html

1. Under "CKEditor plugin settings" > "Drupal link", check the "Linkit
enabled" checkbox.
2. Under "Enabled filters", check the checkbox "Linkit URL converter".
3. Save the text format.

If the 'Limit allowed HTML tags and correct faulty HTML' filter is enabled for
a filter format, then the filter format must be configured to allow the
'target' attribute on 'a' elements in order for linked documents to open in a
new window/tab.
