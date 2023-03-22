<?php

namespace Drupal\Tests\menu_admin_per_menu\Kernel;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Class MenuAdminPerMenuAccessTest.
 *
 * @group menu_admin_per_menu
 *
 * @coversDefaultClass \Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess
 */
class MenuAdminPerMenuAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_admin_per_menu',
    'menu_admin_per_menu_test',
    'link',
    'menu_link_content',
    'menu_ui',
    'system',
    'user',
  ];

  /**
   * A menu entity.
   *
   * @var \Drupal\System\MenuInterface
   */
  protected $menu1;

  /**
   * A menu entity.
   *
   * @var \Drupal\System\MenuInterface
   */
  protected $menu2;

  /**
   * A menu entity.
   *
   * @var \Drupal\System\MenuInterface
   */
  protected $menu3;

  /**
   * A user entity.
   *
   * An anonymous user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * A user entity.
   *
   * An authenticated user without any of the administer menu permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * A user entity.
   *
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user entity.
   *
   * This user has the 'administer menu' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminMenuUser;

  /**
   * A user entity.
   *
   * This user has the 'administer menu_1 permissions'.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $menu1User;

  /**
   * A user entity.
   *
   * This user has the 'administer menu_2 permissions'.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $menu2User;

  /**
   * A user entity.
   *
   * This user has permission to alter menu items on menu_3 because of
   * hook_menu_admin_per_menu_get_permissions_alter that is provided in the
   * test module.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $menu3User;

  /**
   * The allowed menus provider.
   *
   * @var \Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess
   */
  protected $menuAdminPerMenuAllowedMenus;

  /**
   * The menu link content storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkContentStorage;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

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
    $this->installEntitySchema('menu_link_content');
    $this->installSchema('system', 'sequences');
    $this->installConfig('system');

    $this->installEntitySchema('user');
    $this->installConfig('user');

    $this->installConfig('menu_admin_per_menu_test');

    $this->menuAdminPerMenuAllowedMenus = $this->container->get('menu_admin_per_menu.allowed_menus');
    $this->menuLinkContentStorage = $this->container->get('entity_type.manager')->getStorage('menu_link_content');
    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
    $this->menuStorage = $this->container->get('entity_type.manager')->getStorage('menu');

    $this->menu1 = $this->menuStorage->load('menu_1');
    $this->menu2 = $this->menuStorage->load('menu_2');
    $this->menu3 = $this->menuStorage->load('menu_3');

    $this->anonymousUser = new AnonymousUserSession();
    // The admin user is created as first user, so this user has ID 1.
    $this->adminUser = $this->createUser([], 'Admin', TRUE);
    $this->authenticatedUser = $this->createUser([], 'Authenticated user');
    $this->adminMenuUser = $this->createUser(['administer menu'], 'Admin menu user');
    $this->menu1User = $this->createUser(['administer menu_1 menu items'], 'Menu 1 user');
    $this->menu2User = $this->createUser(['administer menu_2 menu items'], 'Menu 2 user');
    // Access to menu_3 is added in menu_admin_per_menu_hook_test.
    $this->menu3User = $this->createUser([], 'Menu 3 user');

    // Make sure that links provided in menu_admin_per_menu_test.links.menu.yml
    // are picked up.
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Test getPerMenuPermissions method.
   *
   * @covers ::getPerMenuPermissions
   */
  public function testGetMenuPermissions() {
    // Anonymous users should not have access to the menus.
    $this->assertEmpty(array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->anonymousUser)));

    // The authenticated user should not have access to the menus since this
    // user has none of the appropriate permissions.
    $this->assertEmpty(array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->authenticatedUser)));

    // Admin user has access to all menus.
    $this->assertEquals([
      'administer admin menu items',
      'administer footer menu items',
      'administer main menu items',
      'administer menu_1 menu items',
      'administer menu_2 menu items',
      'administer menu_3 menu items',
      'administer tools menu items',
      'administer account menu items',
    ],
      array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->adminUser))
    );

    // User with 'administer menu' permission has no menu permission. In the
    // access checks this is fixed by checking the 'administer menu'
    // permission.
    $this->assertEmpty(array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->adminMenuUser)));

    // User with administer menu_1 permission should have access to menu_1.
    $this->assertEquals([
      'administer menu_1 menu items',
    ],
      array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->menu1User))
    );

    // User with administer menu_2 permission should have access to menu_2.
    $this->assertEquals([
      'administer menu_2 menu items',
    ],
      array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->menu2User))
    );

    // User that has access to menu_3 because of the hook implementation should
    // also be listed here.
    $this->assertEquals([
      'administer menu_3 menu items',
    ],
      array_keys($this->menuAdminPerMenuAllowedMenus->getPerMenuPermissions($this->menu3User))
    );
  }

  /**
   * Test result for the menu overview access callback.
   *
   * @covers ::menusOverviewAccess
   */
  public function testMenusOverviewAccess() {
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->anonymousUser));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->authenticatedUser));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->adminUser));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->adminMenuUser));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->menu1User));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->menu2User));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menusOverviewAccess($this->menu3User));
  }

  /**
   * Test menu edit page access callback.
   *
   * @covers ::menuAccess
   */
  public function testMenuAccess() {
    // Anonymous users has no access to one of the menus.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->anonymousUser, $this->menu1));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->anonymousUser, $this->menu2));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->anonymousUser, $this->menu3));

    // Anonymous users has no access to one of the menus.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->authenticatedUser, $this->menu1));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->authenticatedUser, $this->menu2));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->authenticatedUser, $this->menu3));

    // Admin user has access to all menus.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->adminUser, $this->menu1));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->adminUser, $this->menu2));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->adminUser, $this->menu3));

    // User with 'administer menu' permission has access to all menus.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->adminMenuUser, $this->menu1));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->adminMenuUser, $this->menu2));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->adminMenuUser, $this->menu3));

    // Menu 1 user has only access to menu 1.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu1User, $this->menu1));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu1User, $this->menu2));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu1User, $this->menu3));

    // Menu 2 user has only access to menu 2.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu2User, $this->menu1));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu2User, $this->menu2));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu2User, $this->menu3));

    // Menu 3 user has only access to menu 3 because of the hook
    // implementation.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu3User, $this->menu1));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu3User, $this->menu2));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuAccess($this->menu3User, $this->menu3));
  }

  /**
   * Access check for menu link content access callback.
   *
   * @covers ::menuItemAccess
   */
  public function testMenuItemAccess() {
    $menu_1_link = $this->menuLinkContentStorage->create(['menu_name' => $this->menu1->id()]);
    $menu_2_link = $this->menuLinkContentStorage->create(['menu_name' => $this->menu2->id()]);
    $menu_3_link = $this->menuLinkContentStorage->create(['menu_name' => $this->menu3->id()]);

    // Anonymous users has no access to one of the menu items.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->anonymousUser, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->anonymousUser, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->anonymousUser, $menu_3_link));

    // Authenticated user has no access to one of the menu items.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->authenticatedUser, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->authenticatedUser, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->authenticatedUser, $menu_3_link));

    // Admin user has access to all of the menu items.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminUser, $menu_1_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminUser, $menu_2_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminUser, $menu_3_link));

    // User with 'administer menu' permission has access to all menus items.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminMenuUser, $menu_1_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminMenuUser, $menu_2_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminMenuUser, $menu_3_link));

    // Menu 1 user has only access to menu items of menu 1.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu1User, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu1User, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu1User, $menu_3_link));

    // Menu 2 user has only access to menu items of menu 2.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu2User, $menu_1_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu2User, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu2User, $menu_3_link));

    // Menu 3 user has only access to menu items of menu 3 because of the hook
    // implementation.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu3User, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu3User, $menu_2_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->menu3User, $menu_3_link));

    // Make sure that calling the menuItemAccess method without menu link does
    // not result in a fatal error.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuItemAccess($this->adminUser));
  }

  /**
   * Test the access callbacks for menu links provided by *.links.menu.yml.
   *
   * @covers ::menuLinkAccess
   */
  public function testMenuLinkAccess() {
    $menu_1_link = $this->menuLinkManager->getInstance(['id' => 'menu_1.link']);
    $menu_2_link = $this->menuLinkManager->getInstance(['id' => 'menu_2.link']);
    $menu_3_link = $this->menuLinkManager->getInstance(['id' => 'menu_3.link']);

    // Anonymous users has no access to one of the menu items.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->anonymousUser, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->anonymousUser, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->anonymousUser, $menu_3_link));

    // Authenticated user has no access to one of the menu items.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->authenticatedUser, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->authenticatedUser, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->authenticatedUser, $menu_3_link));

    // Admin user has access to all of the menu items.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminUser, $menu_1_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminUser, $menu_2_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminUser, $menu_3_link));

    // User with 'administer menu' permission has access to all menus items.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminMenuUser, $menu_1_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminMenuUser, $menu_2_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminMenuUser, $menu_3_link));

    // Menu 1 user has only access to menu items of menu 1.
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu1User, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu1User, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu1User, $menu_3_link));

    // Menu 2 user has only access to menu items of menu 2.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu2User, $menu_1_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu2User, $menu_2_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu2User, $menu_3_link));

    // Menu 3 user has only access to menu items of menu 3 because of the hook
    // implementation.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu3User, $menu_1_link));
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu3User, $menu_2_link));
    $this->assertEquals(new AccessResultAllowed(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->menu3User, $menu_3_link));

    // Make sure that calling the MenuLinkAccess method without menu link does
    // not result in a fatal error.
    $this->assertEquals(new AccessResultNeutral(), $this->menuAdminPerMenuAllowedMenus->menuLinkAccess($this->adminUser));
  }

}
