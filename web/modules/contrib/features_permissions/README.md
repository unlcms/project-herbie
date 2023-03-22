CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Features Permissions module allows for permissions to be managed
(exported/imported) by the [Features](https://www.drupal.org/project/features) module.

Drupal attaches permissions to roles. When a role is exported, all permissions
are exported with the role. If a role is included in a feature, then all
permissions for that roles are included in that feature. In many instances this
is not desirable. As a result, permissions are stripped by default when
exporting roles. See [User permission handling](https://www.drupal.org/project/features/issues/2383439)
and [Move export processing to a configurable plugin](https://www.drupal.org/project/features/issues/2599278).

The Features Permissions module provides a permission config entity type that
is synced with roles. When a role entity is updated, the associated permission
entities are also updated. This allows for exportability. When a permission
entity is imported or reverted by Features, the associated roles are
also updated.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/features_permissions

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/features_permissions

REQUIREMENTS
------------

This module requires the following modules:

 * [Features](https://www.drupal.org/project/features)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

Upon installation, permission entities are automatically generated from
existing roles.

CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration.

MAINTAINERS
-----------

Current maintainers:
- Chris Burge - https://www.drupal.org/u/chris-burge

This project has been sponsored by:
- [University of Nebraska-Lincoln, Digital Experience Group](https://dxg.unl.edu/)
