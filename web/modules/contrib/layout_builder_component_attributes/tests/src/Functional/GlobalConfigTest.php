<?php

namespace Drupal\Tests\layout_builder_component_attributes\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test global configuration form and storage.
 *
 * @group layout_builder_component_attributes
 */
class GlobalConfigTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'layout_builder_component_attributes',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    // Create authenticated user.
    $this->authUser = $this->drupalCreateUser([
      'access administration pages',
    ]);

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer layout builder component attributes',
    ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests the global config form.
   */
  public function testGlobalConfigForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->authUser);
    $this->drupalGet('/admin/config/content/layout-builder-component-attributes');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/layout-builder-component-attributes');
    $assert_session->pageTextContains('Layout Builder Component Attributes Settings');

    // On install, all attributes are enabled.
    $assert_session->checkboxChecked('allowed_block_attributes[id]');
    $assert_session->checkboxChecked('allowed_block_attributes[class]');
    $assert_session->checkboxChecked('allowed_block_attributes[style]');
    $assert_session->checkboxChecked('allowed_block_attributes[data]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[id]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[class]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[style]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[data]');
    $assert_session->checkboxChecked('allowed_block_content_attributes[id]');
    $assert_session->checkboxChecked('allowed_block_content_attributes[class]');
    $assert_session->checkboxChecked('allowed_block_content_attributes[style]');
    $assert_session->checkboxChecked('allowed_block_content_attributes[data]');

    // Disallow some attributes.
    $page->findField('allowed_block_attributes[id]')->uncheck();
    $page->findField('allowed_block_title_attributes[class]')->uncheck();
    $page->findField('allowed_block_content_attributes[style]')->uncheck();
    $page->findField('allowed_block_content_attributes[data]')->uncheck();
    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');

    // Check updated config values.
    $this->drupalGet('/admin/config/content/layout-builder-component-attributes');
    $assert_session->checkboxNotChecked('allowed_block_attributes[id]');
    $assert_session->checkboxChecked('allowed_block_attributes[class]');
    $assert_session->checkboxChecked('allowed_block_attributes[style]');
    $assert_session->checkboxChecked('allowed_block_attributes[data]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[id]');
    $assert_session->checkboxNotChecked('allowed_block_title_attributes[class]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[style]');
    $assert_session->checkboxChecked('allowed_block_title_attributes[data]');
    $assert_session->checkboxChecked('allowed_block_content_attributes[id]');
    $assert_session->checkboxChecked('allowed_block_content_attributes[class]');
    $assert_session->checkboxNotChecked('allowed_block_content_attributes[style]');
    $assert_session->checkboxNotChecked('allowed_block_content_attributes[data]');

    // Load config.
    $config = \Drupal::service('config.factory')->getEditable('layout_builder_component_attributes.settings');

    // Insert 'langcode' value into config and verify GlobalSettingsForm can
    // handle keys it doesn't define in schema.
    $config->set('langcode', 'en');
    $config->save();

    // Verify storage in config.
    $allowed_block_attributes = $config->get('allowed_block_attributes');
    $expected_value = [
      'id' => FALSE,
      'class' => TRUE,
      'style' => TRUE,
      'data' => TRUE,
    ];
    $this->assertSame($allowed_block_attributes, $expected_value);

    $allowed_block_title_attributes = $config->get('allowed_block_title_attributes');
    $expected_value = [
      'id' => TRUE,
      'class' => FALSE,
      'style' => TRUE,
      'data' => TRUE,
    ];
    $this->assertSame($allowed_block_title_attributes, $expected_value);

    $allowed_block_content_attributes = $config->get('allowed_block_content_attributes');
    $expected_value = [
      'id' => TRUE,
      'class' => TRUE,
      'style' => FALSE,
      'data' => FALSE,
    ];
    $this->assertSame($allowed_block_content_attributes, $expected_value);

    // Reload settings page to verify no warnings, etc. are thrown.
    $this->drupalGet('/admin/config/content/layout-builder-component-attributes');
    $page->pressButton('Save configuration');
  }

}
