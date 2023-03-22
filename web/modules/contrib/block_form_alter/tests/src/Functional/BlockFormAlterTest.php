<?php

namespace Drupal\Tests\block_form_alter\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of block_form_alter module.
 *
 * @group block_form_alter
 */
class BlockFormAlterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'block_form_alter',
    'block_form_alter_test',
    'layout_builder',
    'node',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place certain blocks.
    $this->drupalPlaceBlock('system_menu_block:account');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('system_branding_block', [
      'label' => 'Branding Block',
      'id' => 'systembrandingblock',
    ]);

    // Create a custom block type.
    $bundle = BlockContentType::create([
      'id' => 'test_block',
      'label' => 'Test Block',
      'revision' => FALSE,
    ]);
    $bundle->save();
    block_content_add_body_field('test_block');

    // Create a content type and node.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
      'bypass node access',
      'administer node display',
      'access contextual links',
    ]);
  }

  /**
   * Tests the Block Form Alter functions.
   */
  public function testBlockFormAlter() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);

    // Verify hook_block_plugin_form_alter() is modifying block forms
    // rendered by block_form.
    $this->drupalGet('/admin/structure/block/manage/systembrandingblock');
    $assert_session->checkboxNotChecked('settings[block_branding][use_site_logo]');

    // Verify hook_block_type_form_alter() is modifying custom block add forms
    // rendered by block_content.
    $this->drupalGet('/block/add/test_block');
    $assert_session->fieldValueEquals('body[0][value]', 'test body string');

    $page->fillField('info[0][value]', 'Test Block');
    $page->fillField('body[0][value]', 'Some other text');
    $page->pressButton('Save');

    // Verify hook_block_type_form_alter() is modifying custom block edit forms
    // rendered by block_content.
    $this->drupalGet('/block/1');
    $assert_session->fieldValueEquals('body[0][value]', 'test body string');

    // Enable layout override of test content type.
    // "Borrowed" from LayoutBuilderTest.php.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $this->submitForm(['layout[allow_custom]' => TRUE], 'Save');
    // @todo This should not be necessary.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Update admin user permissions with Layout Builder permissions.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
      'bypass node access',
      'administer node display',
      'access contextual links',
      'configure editable bundle_with_section_field node layout overrides',
      'create and edit custom blocks',
    ]);
    $this->drupalLogin($this->adminUser);

    // Override layout of test node.
    $this->drupalGet('node/1');
    $page->clickLink('Layout');
    $page->pressButton('Save layout');

    // Verify hook_block_plugin_form_alter() is modifying block add forms
    // rendered by layout_builder.
    $this->drupalGet('/layout_builder/add/block/overrides/node.1/0/content/system_branding_block');
    $assert_session->checkboxNotChecked('settings[block_branding][use_site_logo]');

    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Verify hook_block_plugin_form_alter() is modifying block edit forms
    // rendered by layout_builder.
    $this->drupalGet('node/1/layout');
    $element = $page->find('xpath', '//div[contains(@class,"layout-builder-block") and contains(@class,"block-system-branding-block")]');
    $this->drupalGet('/layout_builder/update/block/overrides/node.1/0/content/' . $element->getAttribute('data-layout-block-uuid'));
    $assert_session->checkboxNotChecked('settings[block_branding][use_site_logo]');

    // Verify hook_block_type_form_alter() is modifying custom block forms
    // rendered by layout_builder.
    $this->drupalGet('/layout_builder/add/block/overrides/node.1/0/content/inline_block:test_block');
    $assert_session->fieldValueEquals('settings[block_form][body][0][value]', 'test body string');
  }

}
