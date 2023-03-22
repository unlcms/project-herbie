<?php

namespace Drupal\menu_admin_per_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for menu overview route.
 */
class MenuAdminPerMenuController extends ControllerBase {

  /**
   * The allowed menus provider.
   *
   * @var \Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess
   */
  protected $allowedMenusService;

  /**
   * Constructs a new MenuAdminPerMenu instance.
   *
   * @param \Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess $allowed_menus
   *   The check provider.
   */
  public function __construct(MenuAdminPerMenuAccess $allowed_menus) {
    $this->allowedMenusService = $allowed_menus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu_admin_per_menu.allowed_menus')
    );
  }

  /**
   * Constructs menus overview page.
   */
  public function menuOverviewPage() {
    $account = $this->currentUser();
    $menu_table = $this->entityTypeManager()->getListBuilder('menu')->render();
    if ($account->hasPermission('administer menu')) {
      return $menu_table;
    }
    $allowed_menus = $this->allowedMenusService->getPerMenuPermissions($account);
    foreach ($menu_table['table']['#rows'] as $menu_key => $menu_item) {
      if (!isset($allowed_menus["administer $menu_key menu items"])) {
        unset($menu_table['table']['#rows'][$menu_key]);
      }
    }
    return $menu_table;
  }

}
