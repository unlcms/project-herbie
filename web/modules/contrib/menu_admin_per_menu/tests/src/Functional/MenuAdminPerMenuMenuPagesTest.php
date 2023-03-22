<?php

namespace Drupal\Tests\menu_admin_per_menu\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\menu_admin_per_menu\Traits\MenuLinkContentTrait;

/**
 * Tests the Menu pages in combination with Menu Admin per Menu.
 *
 * @group menu_admin_per_menu
 */
class MenuAdminPerMenuMenuPagesTest extends BrowserTestBase {

  use MenuLinkContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'content_translation',
    'menu_admin_per_menu_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

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
   * An authenticated user without any of the administer menu permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block');

    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Turn on content translation for menu_link_content.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('menu_link_content', 'menu_link_content');
    $config->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->setThirdPartySetting('content_translation', 'enabled', TRUE)
      ->save();

    $this->menuLinkContentStorage = $this->container->get('entity_type.manager')->getStorage('menu_link_content');
    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
    $this->menuStorage = $this->container->get('entity_type.manager')->getStorage('menu');

    $this->menu1 = $this->menuStorage->load('menu_1');
    $this->menu2 = $this->menuStorage->load('menu_2');
    $this->menu3 = $this->menuStorage->load('menu_3');

    $this->authenticatedUser = $this->createUser([], 'Authenticated user');
    $this->adminMenuUser = $this->createUser(['administer menu'], 'Admin menu user');
    $this->menu1User = $this->createUser(['administer menu_1 menu items'], 'Menu 1 user');
    $this->menu2User = $this->createUser(['administer menu_2 menu items'], 'Menu 2 user');
    // Access to menu_3 is added in menu_admin_per_menu_hook_test.
    $this->menu3User = $this->createUser([], 'Menu 3 user');
  }

  /**
   * Test menu overview page.
   */
  public function testMenuOverviewPage(): void {
    $assert_session = $this->assertSession();

    // Anonymous users don't have access to this page.
    $this->drupalGet('admin/structure/menu');
    $assert_session->statusCodeEquals(403);

    // An authenticated user without permissions doesn't have access to this
    // page.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('admin/structure/menu');
    $assert_session->statusCodeEquals(403);

    // Admin users have access to all menus.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/structure/menu');

    $assert_session->statusCodeEquals(200);
    // Make sure the title on the menus overview page is present.
    $assert_session->responseContains('<h1>Menus</h1>');
    $assert_session->pageTextContains('Menu 1 menu');
    $assert_session->pageTextContains('Menu 2 menu');
    $assert_session->pageTextContains('Menu 3 menu');

    // A user with 'administer menu' permission has access to all menus.
    $this->drupalLogin($this->adminMenuUser);
    $this->drupalGet('admin/structure/menu');

    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Menu 1 menu');
    $assert_session->pageTextContains('Menu 2 menu');
    $assert_session->pageTextContains('Menu 3 menu');

    // A user with 'administer menu_1 menu items' only has access to menu_1.
    $this->drupalLogin($this->menu1User);
    $this->drupalGet('admin/structure/menu');

    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Menu 1 menu');
    $assert_session->pageTextNotContains('Menu 2 menu');
    $assert_session->pageTextNotContains('Menu 3 menu');

    // A user with 'administer menu_2 menu items' only has access to menu_2.
    $this->drupalLogin($this->menu2User);
    $this->drupalGet('admin/structure/menu');

    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('Menu 1 menu');
    $assert_session->pageTextContains('Menu 2 menu');
    $assert_session->pageTextNotContains('Menu 3 menu');

    // Permission for this user was added by
    // hook_menu_admin_per_menu_get_permissions_alter.
    $this->drupalLogin($this->menu3User);
    $this->drupalGet('admin/structure/menu');

    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('Menu 1 menu');
    $assert_session->pageTextNotContains('Menu 2 menu');
    $assert_session->pageTextContains('Menu 3 menu');
  }

  /**
   * Test menu edit and menu link add form.
   */
  public function testMenuEditAndMenuLinkAddForm(): void {
    $assert_session = $this->assertSession();

    // Anonymous users don't have access to this page.
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(403);

    // An authenticated user without permissions doesn't have access to this
    // page.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(403);

    // Admin users have access to all menus.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(200);
    $this->clickLink('Add link');
    $assert_session->statusCodeEquals(200);

    // Check if adding menu items still work.
    $this->submitForm([
      'title[0][value]' => 'Test link',
      'link[0][uri]' => '<front>',
    ], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    // Check if admin user has access to menu_2.
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu2->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu2->id()));
    $assert_session->statusCodeEquals(200);

    // Check if admin user has access to menu_3.
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu3->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu3->id()));
    $assert_session->statusCodeEquals(200);

    // User with 'administer menu' permission can edit all menus.
    $this->drupalLogin($this->adminMenuUser);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(200);

    // Check that the menu properties are available for a user with the
    // 'administer menu' permission.
    $assert_session->fieldExists('id');
    $assert_session->fieldExists('label');
    $assert_session->fieldExists('description');
    $assert_session->fieldExists('langcode');

    $this->clickLink('Add link');
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is not filtered.
    $assert_session->optionExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionExists('menu_parent', 'menu_3:menu_3.link');

    $this->submitForm([
      'title[0][value]' => 'Test link',
      'link[0][uri]' => '<front>',
    ], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu2->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu2->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu3->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu3->id()));
    $assert_session->statusCodeEquals(200);

    // User with 'administer menu_1 menu items' can only edit menu_1.
    $this->drupalLogin($this->menu1User);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(200);

    // Check that the menu properties are not available for a user without the
    // 'administer menu' permission.
    $assert_session->fieldNotExists('id');
    $assert_session->fieldNotExists('label');
    $assert_session->fieldNotExists('description');
    $assert_session->fieldNotExists('langcode');

    $this->clickLink('Add link');
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is filtered.
    $assert_session->optionExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionNotExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionNotExists('menu_parent', 'menu_3:menu_3.link');

    $this->submitForm([
      'title[0][value]' => 'Test link',
      'link[0][uri]' => '<front>',
    ], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu2->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu2->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu3->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu3->id()));
    $assert_session->statusCodeEquals(403);

    // User with 'administer menu_2 menu items' can only edit menu_2.
    $this->drupalLogin($this->menu2User);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu1->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu2->id()));
    $assert_session->statusCodeEquals(200);
    $this->clickLink('Add link');
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is filtered.
    $assert_session->optionNotExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionNotExists('menu_parent', 'menu_3:menu_3.link');

    $this->submitForm([
      'title[0][value]' => 'Test link',
      'link[0][uri]' => '<front>',
    ], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu3->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu3->id()));
    $assert_session->statusCodeEquals(403);

    // Permission for this user was added by
    // hook_menu_admin_per_menu_get_permissions_alter.
    $this->drupalLogin($this->menu3User);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu1->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu1->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu2->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/manage/%s/add', $this->menu2->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/manage/%s', $this->menu3->id()));
    $assert_session->statusCodeEquals(200);
    $this->clickLink('Add link');
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is filtered.
    $assert_session->optionNotExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionNotExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionExists('menu_parent', 'menu_3:menu_3.link');

    $this->submitForm([
      'title[0][value]' => 'Test link',
      'link[0][uri]' => '<front>',
    ], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');
  }

  /**
   * Test the menu_link_plugin edit and menu_link_plugin reset form.
   */
  public function testLinkEditAndResetForm(): void {
    $assert_session = $this->assertSession();

    $menu_1_link = $this->menuLinkManager->getInstance(['id' => 'menu_1.link']);
    $menu_2_link = $this->menuLinkManager->getInstance(['id' => 'menu_2.link']);
    $menu_3_link = $this->menuLinkManager->getInstance(['id' => 'menu_3.link']);

    // Anonymous users don't have access to this page.
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    // An authenticated user without permissions doesn't have access to this
    // page.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    // Admin users have access to all menus.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Reset');
    $assert_session->pageTextContains('The menu link was reset to its default settings.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(200);

    // Users with 'administer menu' permission have acces to all menus.
    $this->drupalLogin($this->adminMenuUser);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Reset');
    $assert_session->pageTextContains('The menu link was reset to its default settings.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(200);

    // Users with 'administer menu_1 menu items' only have access to menu_1.
    $this->drupalLogin($this->menu1User);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Reset');
    $assert_session->pageTextContains('The menu link was reset to its default settings.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    // Users with 'administer menu_2 menu items' only have access to menu_2.
    $this->drupalLogin($this->menu2User);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Reset');
    $assert_session->pageTextContains('The menu link was reset to its default settings.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    // Permission for this user was added by
    // hook_menu_admin_per_menu_get_permissions_alter.
    $this->drupalLogin($this->menu3User);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_1_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_2_link->getPluginId()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/edit', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('The menu link has been saved.');

    $this->drupalGet(sprintf('admin/structure/menu/link/%s/reset', $menu_3_link->getPluginId()));
    $assert_session->statusCodeEquals(200);
    $this->submitForm([], 'Reset');
    $assert_session->pageTextContains('The menu link was reset to its default settings.');
  }

  /**
   * Test the menu_link_content pages.
   */
  public function testMenuLinkContentPages() {
    $assert_session = $this->assertSession();

    $menu_1_link = $this->createMenuContentLink(['menu_name' => $this->menu1->id()]);
    $menu_2_link = $this->createMenuContentLink(['menu_name' => $this->menu2->id()]);
    $menu_3_link = $this->createMenuContentLink(['menu_name' => $this->menu3->id()]);

    // Anonymous users doesn't have access to any of the pages.
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);

    // An authenticated user without permissions doesn't have access to this
    // page.
    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);

    // Admin users have access to all menus.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);

    // Users with the 'administer menu' permission can edit all menus and menu
    // links.
    $this->drupalLogin($this->adminMenuUser);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is not filtered.
    $assert_session->optionExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionExists('menu_parent', 'menu_3:menu_3.link');

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);

    // Users with 'administer menu_1 menu items' only have access to menu_1.
    $this->drupalLogin($this->menu1User);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is filtered.
    $assert_session->optionExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionNotExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionNotExists('menu_parent', 'menu_3:menu_3.link');

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);

    // Users with 'administer menu_2 menu items' only have access to menu_2.
    $this->drupalLogin($this->menu2User);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is filtered.
    $assert_session->optionNotExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionNotExists('menu_parent', 'menu_3:menu_3.link');

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(200);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(403);

    // Permission for this user was added by
    // hook_menu_admin_per_menu_get_permissions_alter.
    $this->drupalLogin($this->menu3User);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_1_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_2_link->id()));
    $assert_session->statusCodeEquals(403);

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);

    // Check that list of parent options is filtered.
    $assert_session->optionNotExists('menu_parent', 'menu_1:menu_1.link');
    $assert_session->optionNotExists('menu_parent', 'menu_2:menu_2.link');
    $assert_session->optionExists('menu_parent', 'menu_3:menu_3.link');

    $this->drupalGet(sprintf('admin/structure/menu/item/%s/delete', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
    $this->drupalGet(sprintf('admin/structure/menu/item/%s/edit/translations/add/en/fr', $menu_3_link->id()));
    $assert_session->statusCodeEquals(200);
  }

}
