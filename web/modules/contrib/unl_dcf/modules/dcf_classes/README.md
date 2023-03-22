INTRODUCTION
------------

This module allows for a site builder to define a whitelist of [Digital Campus Framework](https://github.com/d-c-n/dcf) (DCF) classes to make available to content creators.

In the DCF framework, CSS classes are used for everything from adding padding to an element to defining page layouts. In some cases, it makes sense to expose a subset of these classes to content creators.

This module currently supports the following definitions:
  1. Heading Classes
  2. Section Classes
  3. Section Packages

*Heading Classes* could be employed anywhere on a site where a heading is used. For example, a site builder may want content creators to have the option of applying a utility class to override the font-size of a heading.

*Section Classes* and *Section Packages* are designed for use with *Layout Builder*. A site builder may wish to allow content creators to apply any number of layout, padding, or background classes to a *Layout Builder* section. These classes are whitelisted in the *Section Classes* definition. A *Section Package* is simply a predefined grouping of classes, such as `dcf-bleed dcf-wrapper dcf-pt-5 dcf-pb-5 mytheme-bg-red` being made available as the `Red section` package.

See the `dcf/dcf_classes/example/dcf_classes.classes.yml` configuration file for examples.

The *DCF Classes* module provides a user interface and configuration storage for these definitions; however, it doesn't do anything with them. It is up to other modules, such as *DCF Layouts*, to make use of them.

REQUIREMENTS
------------

This module requires the following modules/libraries:

  * [Digital Campus Framework](https://github.com/d-c-n/dcf) (DCF)

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

CONFIGURATION
-------------

  * Navigate to the *DCF Classes* configuration page
    (/admin/config/content/dcf/classes)
  * On the configuration page, define the allowed classes and packages
