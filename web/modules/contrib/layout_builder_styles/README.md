CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration

INTRODUCTION
------------

Layout Builder Styles allows site builders to select from a list of styles to
apply to layout builder blocks and layout builder sections. A "style" is just
a representation of one or more CSS classes that will be applied. Additionally,
for blocks, a block template suggestion is added for the selected style.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/layout_builder_styles/

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/layout_builder_styles/


REQUIREMENTS
------------

* This module requires, at minimum, Drupal 8.7.0.
* This module requires no additional modules.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------

When placing a block into a layout, this module will check to see if any block
styles are available for the block type, and if so, present the user with a
select list to choose one to apply.

A simple user interface is provided for managing the styles available. Since a
style is a configuration entity, it can be exported and imported just like any
other configuration data on your site.

 * For full configuration documentation, please see the contributed
   documentation: https://www.drupal.org/docs/8/modules/layout-builder-styles
