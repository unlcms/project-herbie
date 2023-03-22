<?php

namespace Drupal\Tests\menu_admin_per_menu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_admin_per_menu\MenuAdminPerMenuPermissions;

/**
 * Class MenuAdminPerMenuPermissionsTest.
 *
 * @group menu_admin_per_menu
 *
 * @coversDefaultClass \Drupal\menu_admin_per_menu\MenuAdminPerMenuPermissions
 */
class MenuAdminPerMenuPermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_admin_per_menu',
    'menu_ui',
    'system',
  ];

  /**
   * The menu admin per menu permission callback.
   *
   * @var \Drupal\menu_admin_per_menu\MenuAdminPerMenuPermissions
   */
  protected $menuAdminPerMenuPermissions;

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('menu');
    $this->installConfig('system');

    $this->menuAdminPerMenuPermissions = new MenuAdminPerMenuPermissions();
    $this->menuStorage = $this->container->get('entity_type.manager')->getStorage('menu');
  }

  /**
   * Test the permissions created by MenuAdminPerMenuPermissions.
   *
   * @covers ::permissions
   */
  public function testPermissions() {
    $this->assertEquals([
      'administer admin menu items',
      'administer footer menu items',
      'administer main menu items',
      'administer tools menu items',
      'administer account menu items',
    ], array_keys($this->menuAdminPerMenuPermissions->permissions()));

    $menu = $this->menuStorage->create([
      'id' => 'my_custom_menu',
      'label' => 'My custom menu',
    ]);
    $menu->save();

    $this->assertEquals([
      'administer admin menu items',
      'administer footer menu items',
      'administer main menu items',
      'administer my_custom_menu menu items',
      'administer tools menu items',
      'administer account menu items',
    ], array_keys($this->menuAdminPerMenuPermissions->permissions()));

    $this->menuStorage->delete([$menu]);

    $this->assertEquals([
      'administer admin menu items',
      'administer footer menu items',
      'administer main menu items',
      'administer tools menu items',
      'administer account menu items',
    ], array_keys($this->menuAdminPerMenuPermissions->permissions()));
  }

}
