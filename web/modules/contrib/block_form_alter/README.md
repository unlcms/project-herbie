## INTRODUCTION

The Block Form Alter module provides functions to alter block forms consistently
across implementing plugins:

* `hook_block_plugin_form_alter()`
* `hook_block_type_form_alter()`

Block forms are rendered by implementing plugins, which may require duplicate
code in some instances where a developer desires to alter a given block form.
This is particularly true when altering forms rendered by Layout Builder.

For a full description of the module, visit the project page:
https://www.drupal.org/project/block_form_alter

To submit bug reports and feature suggestions, or track changes:
https://www.drupal.org/project/issues/block_form_alter

### hook_block_plugin_form_alter()

The `hook_block_plugin_form_alter()` function allows for block forms to be
targeted by plugin id. Block forms rendered by the 'block_content' and
'inline_block' plugins must be altered with `hook_block_type_form_alter()`.
See block_form_alter.api.php for details.

### hook_block_type_form_alter()

The `hook_block_type_form_alter()` function modifies block forms rendered by
both Block Content ('block_content' plugin) and Layout Builder ('inline_block'
plugin). See block_form_alter.api.php for details.

### Related Core Issue

* [# 3028391: It's very difficult to alter forms of inline (content blocks) placed via Layout Builder](https://www.drupal.org/project/drupal/issues/3028391)

## REQUIREMENTS

This module requires the following modules:

 * Block (core)

## SUPPORTED MODULES

 * Block (core)
 * Block Content (core)
 * Layout Builder (core)

## INSTALLATION
 
 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

## CONFIGURATION

The module has no menu or modifiable settings. There is no configuration. When
enabled, the module will provide the two form alter functions.

## MAINTAINERS

Current maintainers:
 * Chris Burge - https://www.drupal.org/u/chris-burge

This project has been sponsored by:
 * [University of Nebraska-Lincoln, Internet and Interactive Media](https://iim.unl.edu/)
