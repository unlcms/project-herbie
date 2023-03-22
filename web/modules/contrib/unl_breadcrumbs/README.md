INTRODUCTION
------------

This module allows for core's path-generated breadcrumbs to be modified
according to UNL web guidelines.

  * The root breadcrumb for all sites is "Nebraska", and it links 
    to https://www.unl.edu. This is not configurable.
  * For non-flagship sites, there is an optional Site Root Breadcrumb
    Title, which prints immediately after the "Nebraska" root item.
    This is configurable.
  * The menu link title (unlinked) is printed as the final breadcrumb item.
    In the event the page is not in a menu, its page title is
    printed instead. This is not configurable.

The default breadcrumb for an "Example" page might be as follows:

[Home](https://example.com) > [Parent Item](https://example.com/parent)

The UNL Breadcrumb for this same page would be as follows:

[Nebraska](https://www.unl.edu) > [[Site Root Breadcrumb Title]](https://example.com/) > [Parent Item](https://example.com/parent) > Example Page

## Interstitial Breadcrumbs
Interstitial breadcrumbs allow an organizational hierarchy back to www.unl.edu
in the event this site is not a child of www.unl.edu. For example, if the
Center for Excellent Examples, which is part of the College of Examples, has
its own website, then the default breadcrumbs would be "Nebraska > Center for
Excellent Examples" instead of "Nebraska > College of Examples > Center for
Excellent Examples". Interstitial breadcrumbs allow missing breadcrumbs to be
added for a complete hierarchy. Interstitial breadcrumbs are inserted between
"Nebraska" and the site root breadcrumb.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

CONFIGURATION
-------------

All configuration is global for a given Drupal site.

  * Navigate to the UNL Breadcrumbs configuration page
    (/admin/config/user-interface/unl-breadcrumbs)

MAINTAINERS
-----------

Current maintainers:
  * Chris Burge - https://www.drupal.org/u/chris-burge
