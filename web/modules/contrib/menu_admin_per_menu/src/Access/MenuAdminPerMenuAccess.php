<?php

namespace Drupal\menu_admin_per_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_admin_per_menu\MenuAdminPerMenuAccessInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;

/**
 * Checks access for displaying administer menu pages.
 */
class MenuAdminPerMenuAccess implements MenuAdminPerMenuAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function getPerMenuPermissions(AccountInterface $account) {
    $perms_menu = &drupal_static(__FUNCTION__, []);

    if (!isset($perms_menu[$account->id()])) {
      $menus = array_map(function ($menu) {
        return $menu->label();
      }, Menu::loadMultiple());
      asort($menus);
      foreach ($menus as $name => $title) {
        $permission = 'administer ' . $name . ' menu items';
        if ($account->hasPermission($permission)) {
          $perms_menu[$account->id()][$permission] = $name;
        }
      }
      $user_perms_menu = $perms_menu[$account->id()] ?? [];
      \Drupal::moduleHandler()->alter('menu_admin_per_menu_get_permissions', $user_perms_menu, $account);
      $perms_menu[$account->id()] = $user_perms_menu;
    }

    return $perms_menu[$account->id()] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function menusOverviewAccess(AccountInterface $account) {
    if ($account->hasPermission('administer menu')) {
      return AccessResult::allowed();
    }
    $permissions = $this::getPerMenuPermissions($account);
    if ($account->hasPermission('administer menu') || $permissions) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function menuAccess(AccountInterface $account, Menu $menu) {
    $permission = 'administer ' . $menu->get('id') . ' menu items';
    $permissions = $this::getPerMenuPermissions($account);
    if ($account->hasPermission('administer menu')
      || isset($permissions[$permission])) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function menuItemAccess(AccountInterface $account, MenuLinkContent $menu_link_content = NULL) {
    if (!$menu_link_content instanceof MenuLinkContent) {
      return AccessResult::neutral();
    }
    $permission = 'administer ' . $menu_link_content->getMenuName() . ' menu items';
    $permissions = $this::getPerMenuPermissions($account);
    if ($account->hasPermission('administer menu')
      || isset($permissions[$permission])) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function menuLinkAccess(AccountInterface $account, MenuLinkInterface $menu_link_plugin = NULL) {
    if (!$menu_link_plugin instanceof MenuLinkInterface) {
      return AccessResult::neutral();
    }
    $permission = 'administer ' . $menu_link_plugin->getMenuName() . ' menu items';
    $permissions = $this::getPerMenuPermissions($account);
    if ($account->hasPermission('administer menu')
      || isset($permissions[$permission])) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
