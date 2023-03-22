<?php

namespace Drupal\menu_admin_per_menu;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;

/**
 * Provides an interface defining a MenuAdminPerMenuAccess manager.
 */
interface MenuAdminPerMenuAccessInterface {

  /**
   * Return array of all specific menu permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object for the user whose menu access is being checked.
   *
   * @return array
   *   The array of allowed menus, keyed with permission.
   */
  public function getPerMenuPermissions(AccountInterface $account);

  /**
   * A custom access check for menu overview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menusOverviewAccess(AccountInterface $account);

  /**
   * A custom access check for menu page and add link page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\system\Entity\Menu $menu
   *   Run access checks for this menu object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuAccess(AccountInterface $account, Menu $menu);

  /**
   * A custom access check for menu items page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link_content
   *   Run access checks for this menu item object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuItemAccess(AccountInterface $account, MenuLinkContent $menu_link_content = NULL);

  /**
   * A custom access check for menu link page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin
   *   Run access checks for this menu link object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function menuLinkAccess(AccountInterface $account, MenuLinkInterface $menu_link_plugin = NULL);

}
