CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Drupal 8 core does not provide support for block type templates.

The Block Type Templates module provides modular design components, affording
consistent patterns driven through Drupal's block system. This can be used with
the standard block placement, Panels, or any other system that leverages blocks.
Advanced supported cases include the use of the Components module, which can
afford more granular, reusable templates included within other templates.

 * For a full description of the module visit:
   https://www.drupal.org/project/block_type_templates

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/block_type_templates


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Block Type Templates module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

This module does not need any additional configurations.

The Block Type Templates module provides the following theme suggestions:

```block--block-content-{{ machine-name }}.html.twig```
```block--block-content-{{ machine-name }}--{{ view-mode }}.html.twig```

For example, a custom block type with machine name testing_this_out would now
have a corresponding Twig template for all blocks of that type 
block--block-content-testing-this-out.html.twig. In the case of a 'teaser' view mode for 
the same custom block type, a block--block-content-testing-this-out--teaser.html.twig 
Twig template would be available.

The Twig template assumes all of the standard markup found in all of the other
block templates, including the corresponding fields of that block type.



MAINTAINERS
-----------

 * Adam Bergstein (nerdstein) - https://www.drupal.org/u/nerdstein

Supporting organization:

Ideation and Development:
 * Civic Actions - https://www.drupal.org/civicactions
