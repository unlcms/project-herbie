<?php

namespace Drupal\Tests\menu_admin_per_menu\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests a menu reference field in combination with Menu Admin per Menu.
 *
 * @group menu_admin_per_menu
 */
class MenuAdminPerMenuEntityReferenceTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'menu_admin_per_menu_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

    $this->drupalCreateContentType(
      [
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]
    );

    // Create an entity reference field for menus and make it required.
    $this->createEntityReferenceField('node', 'page', 'field_menu', 'Menu', 'menu');
    $field_config = FieldConfig::loadByName('node', 'page', 'field_menu');
    $field_config->setRequired(TRUE);
    $field_config->save();

    // Add the field to the node form.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page')
      ->setComponent('field_menu', ['type' => 'options_select'])
      ->save();

    $this->adminMenuUser = $this->createUser([
      'access content',
      'administer content types',
      'create page content',
      'edit any page content',
      'administer menu',
    ], 'Admin menu user');
    $this->menu1User = $this->createUser([
      'access content',
      'administer content types',
      'create page content',
      'edit any page content',
      'administer menu_1 menu items',
    ], 'Menu 1 user');
    $this->menu2User = $this->createUser([
      'access content',
      'administer content types',
      'create page content',
      'edit any page content',
      'administer menu_2 menu items',
    ], 'Menu 2 user');
    // Access to menu_3 is added in menu_admin_per_menu_hook_test.
    $this->menu3User = $this->createUser([
      'access content',
      'administer content types',
      'create page content',
      'edit any page content',
    ], 'Menu 3 user');
  }

  /**
   * Test Menu Admin per Menu permissions in an entity reference field.
   */
  public function testEntityReferenceField() {
    $assert_session = $this->assertSession();

    // User with 'administer menu' permission can access all menus.
    $this->drupalLogin($this->adminMenuUser);
    $this->drupalGet('node/add/page');
    $assert_session->selectExists('field_menu');
    $assert_session->optionExists('field_menu', 'menu_1');
    $assert_session->optionExists('field_menu', 'menu_2');
    $assert_session->optionExists('field_menu', 'menu_3');

    // User with 'adminiser menu_1 menu items' can only access menu 1.
    $this->drupalLogin($this->menu1User);
    $this->drupalGet('node/add/page');
    $assert_session->selectExists('field_menu');
    $assert_session->optionExists('field_menu', 'menu_1');
    $assert_session->optionNotExists('field_menu', 'menu_2');
    $assert_session->optionNotExists('field_menu', 'menu_3');

    // User with 'adminiser menu_2 menu items' can only access menu 2.
    $this->drupalLogin($this->menu2User);
    $this->drupalGet('node/add/page');
    $assert_session->selectExists('field_menu');
    $assert_session->optionNotExists('field_menu', 'menu_1');
    $assert_session->optionExists('field_menu', 'menu_2');
    $assert_session->optionNotExists('field_menu', 'menu_3');

    // Permission for this user was added by
    // hook_menu_admin_per_menu_get_permissions_alter.
    $this->drupalLogin($this->menu3User);
    $this->drupalGet('node/add/page');
    $assert_session->selectExists('field_menu');
    $assert_session->optionNotExists('field_menu', 'menu_1');
    $assert_session->optionNotExists('field_menu', 'menu_2');
    $assert_session->optionExists('field_menu', 'menu_3');
  }

  /**
   * Test resaving a menu entity reference field when the user has no access.
   */
  public function testResaveEntityReferenceWithoutAccess() {
    $assert_session = $this->assertSession();

    // Create a node with user 1. This user can only access menu_1.
    $this->drupalLogin($this->menu1User);

    $title = $this->randomString();
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => $title,
      'field_menu' => 'menu_1',
    ], 'Save');

    $node = $this->drupalGetNodeByTitle($title);
    // Check that field_menu references menu_1.
    $this->assertEquals('menu_1', $node->get('field_menu')->target_id);

    // Login as user 2 and resave the node. User 2 can only access menu_2.
    $this->drupalLogin($this->menu2User);
    $this->drupalGet(sprintf('node/%s/edit', $node->id()));
    $submit_button = $assert_session->buttonExists('Save');
    $submit_button->click();

    $node = $this->drupalGetNodeByTitle($title, TRUE);
    // Check that the node still has menu_1 as value.
    $this->assertEquals('menu_1', $node->get('field_menu')->target_id);
  }

}
