# CONTENTS OF THIS FILE

- Introduction
- Requirements
- Installation
- Configuration
- Usage
- Maintainers

# INTRODUCTION

The *Layout Builder Component Attributes* module allows editors to add HTML
attributes to *Layout Builder* components (blocks). Attributes can be added
to 1) the block (outer) element, 2) the block title, and 3) the block content
(inner) element.

The following attributes are supported:

- ID
- Class(es)
- Style
- Data-* attributes

*Note: When a block is placed into a Layout Builder section, it is placed as a
Layout Builder component.*

- For a full description of the module, visit the project page:
  https://www.drupal.org/project/layout_builder_component_attributes

- To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/layout_builder_component_attributes

# REQUIREMENTS

This mode has the following requirements:

- Drupal 8.8.4+
- *Layout Builder* (included in Drupal core)
- *Php CSS Lint* (https://github.com/neilime/php-css-lint)
  - Installed with Composer or Ludwig.

# INSTALLATION

- Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.
- It is recommended to install this module with Composer.
- This module also supports Ludwig (a Composer alternative) for dependencies.
  See [Ludwig](https://www.drupal.org/project/ludwig)] for more information.

# CONFIGURATION

Upon installation, it is necessary to configure the module's global settings
and to grant permissions, as necessary.

## Permissions

The module defines the following permissions:

- Administer Layout Builder Component Attributes
  - Users with this permission are able to manage global settings for the
  *Layout Builder Component Attributes* module
- Manage Layout Builder component attributes
  - Users with this permission are able to add attributes to
  *Layout Builder* components (blocks)

## Global Configuration

On the *Layout Builder Component Attributes Settings* configuration page
(/admin/config/content/layout-builder-component-attributes), site
administrators can control which attributes are allowed on 1) the block
(outer) element, 2) the block title, and 3) the block content (inner) element.

If an attribute is allowed and then later disallowed, any disallowed attributes
will no longer be rendered.

# USAGE

Users with the *Manage Layout Builder component attributes* permission will be
able to add attributes to *Layout Builder* components.

Follow the steps below to add attributes:

1. On an entity's layout page (e.g./node/1/layout), click on the
  'Manage attributes' contextual link for a given component.
1. Add attributes as desired.
1. Click the 'Update' button.

## Block Template

In order for attributes to be rendered on the the block content (inner)
element, the active front-end theme must support `content_attributes` in its
block.html.twig file. In the event a theme does not include a block.html.twig
file, it will inherit from its parent theme or from the Block module.

Example from Bartik (with content_attributes):

```
<div{{ attributes.addClass(classes) }}>
  {{ title_prefix }}
  {% if label %}
    <h2{{ title_attributes }}>{{ label }}</h2>
  {% endif %}
  {{ title_suffix }}
  {% block content %}
    <div{{ content_attributes.addClass('content') }}>
      {{ content }}
    </div>
  {% endblock %}
</div>
```

Example from Classy (without content_attributes):

```
<div{{ attributes.addClass(classes) }}>
  {{ title_prefix }}
  {% if label %}
    <h2{{ title_attributes }}>{{ label }}</h2>
  {% endif %}
  {{ title_suffix }}
  {% block content %}
    {{ content }}
  {% endblock %}
</div>
```

Below is a summary of the various block.html.twig template files included
in Drupal core:

- Block (module) - content_attributes is NOT rendered
- Bartik - content_attributes IS rendered
- Claro - content_attributes is NOT rendered
- Classy - content_attributes is NOT rendered
- Seven - content_attributes is NOT rendered
- Stable - content_attributes is NOT rendered
- Stark - content_attributes is NOT rendered

# MAINTAINERS

Current maintainers:
- Chris Burge - https://www.drupal.org/u/chris-burge

This project has been sponsored by:
- [University of Nebraska-Lincoln, Digital Experience Group](https://dxg.unl.edu/)
