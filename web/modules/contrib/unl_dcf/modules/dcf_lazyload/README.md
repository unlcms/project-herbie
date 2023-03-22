INTRODUCTION
------------

This module allows for a site builder to use *DCF Lazy Loading* with Drupal. *DCF Lazy Loading* is part of the [Digital Campus Framework](https://github.com/d-c-n/dcf).

This module supports the base lazying loading functionality where images are loaded prior to entering the viewport. It also provides the ability to automatically calculate the sizes attribute based on the parent element. This is useful in instances when the *size* of the parent element may not be known when rendering the image markup.

Currently, this module supports the Responsive Image formatter.

REQUIREMENTS
------------

This module requires the following modules/libraries:

  * Responsive Image (included in Drupal core)
  * [Digital Campus Framework](https://github.com/d-c-n/dcf) (DCF) \[Optional\]

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

CONFIGURATION
-------------

## Image Formatter Configuration

*DCF Lazy Loading* can be enabled and configured on individual fields on a per-view-mode basis:

  1. Navigate to the *Manage Display* page for any entity view mode.
  2. Set the *Format* for an image field to "Responsive Image".
  3. Open the formatter configuration and check the "Enable DCF Lazy Loading" checkbox.
  4. Optionally, check the "Automatically calculate 'sizes' attribute" checkbox.

## Global Configuration

*DCF Lazy Loading* requires CSS and Javascript in order to function. By default, these assets are provided by the *DCF Lazy Loading* module. They can also be provided externally. This may be the case with a DCF-based theme.

  1. Navigate to the *DCF Lazy Loading* configuration page: (admin/config/media/dcf/lazyload)
  2. Select either "DCF Lazy Loading module" or "Externally loaded" for the "Assets Source" field.
