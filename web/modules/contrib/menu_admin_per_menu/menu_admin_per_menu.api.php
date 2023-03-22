<?php

/**
 * @file
 * Hooks provided by the Menu Admin per Menu module.
 */

use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the menus for which a user has per menu admin permissions.
 *
 * @param array $perm_menus
 *   The $perm_menus array returned by getPerMenuPermissions()
 *   for a user account. Values in array are menu machine names and keys are
 *   permission name for appropriate menu.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account object.
 *
 * @see \Drupal\menu_admin_per_menu\MenuAdminPerMenuAccessInterface::getPerMenuPermissions()
 * @ingroup menu
 */
function hook_menu_admin_per_menu_get_permissions_alter(array &$perm_menus, AccountInterface $account) {

  // Our sample module never allows certain roles to edit or delete
  // content. Since some other node access modules might allow this
  // permission, we expressly remove it by returning an empty $grants
  // array for roles specified in our variable setting.
  if ($account->id()) {
    $perm_menus['administer custom-menu menu items'] = 'custom-menu';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
