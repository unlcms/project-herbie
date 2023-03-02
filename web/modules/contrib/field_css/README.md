# CONTENTS OF THIS FILE

- Introduction
- Requirements
- Recommended modules
- Installation
- Configuration
- Usage
- Maintainers

# INTRODUCTION

The Field CSS module provides a field that 1) accepts CSS, 2) does minimal
processing, and 3) adds the CSS to the entity display. It can be added to
any bundle.

- For a full description of the module, visit the project page:
  https://www.drupal.org/project/field_css

- To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/field_css

# REQUIREMENTS

This module requires no modules outside of Drupal core.

# RECOMMENDED MODULES

- CodeMirror Editor (https://www.drupal.org/project/codemirror_editor):
  When enabled, the CodeMirror editor will be enabled for CSS fields.

# INSTALLATION

- Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.

# CONFIGURATION
 
- Configure the user permissions in Administration » People » Permissions:

  - Access CSS fields

    Allows a user to edit the contents of a CSS field. *This permission should
    only be granted to trusted users.*

# USAGE

- To add a CSS field to a bundle, select 'CSS' as the field type when adding a new field.
- The widget and formatter will be automatically assigned.

# MAINTAINERS

Current maintainers:
- Chris Burge (chris-burge) - https://www.drupal.org/u/chris-burge
