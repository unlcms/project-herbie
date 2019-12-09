INTRODUCTION
------------

This module allows for certain configuration to be managed by site owners on a sub-config object basis. For example, in the case of the `system.site` config object, it is possible to allow site owners to edit the _Site Name_ and _Default front page_ settings while denying edit access to other settings, such as _Email address_ or _Default 403 (access denied) page_.

REQUIREMENTS
------------

 - Drupal 8
 - Config Ignore

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

USAGE AND CONFIGURATION
-------------

There are four components of functionality:

## Permission
This module adds the `unl administer site configuration` permission, which is used to provide access to certain routes where access would otherwise be controlled solely by the `administer site configuration` permission.

## Alter route permission
In order to grant access to routes for roles with the `unl administer site configuration` permission, this module provides a route subscriber service and an accompanying `RouteSubscriber` class. In this class, a given route's `_permission` requirement is modified to permit access using OR logic with the `administer site configuration` and the `unl administer site configuration` permissions. Either permission will now provide access to the route.

## Alter form
Now that a user with the `unl administer site configuration` permission has access to the route and its form page, access can be further restricted with `hook_form_FORM_ID_alter()`. A form field can be disabled by adding `'#disabled' => TRUE` to the form element's render array. This designation is enforced in the backend on submission by Drupal core. This restriction code is conditionally applied to roles without the `administer site configuration` permission.

## Ignored configuration
Finally, the Config Ignore configuration must be updated to ignore the configuration that site owners are allowed to edit.
