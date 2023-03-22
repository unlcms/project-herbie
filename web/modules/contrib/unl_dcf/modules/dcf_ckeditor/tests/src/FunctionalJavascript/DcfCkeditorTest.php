<?php

namespace Drupal\Tests\dcf_ckeditor\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;

/**
 * Tests DCF CKEditor integration.
 *
 * @group dcf_ckeditor
 */
class DcfCkeditorTest extends WebDriverTestBase {

  use CKEditorTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'ckeditor',
    'filter',
    'dcf_ckeditor',
  ];

  /**
   * The configured text editor entity.
   *
   * @var \Drupal\editor\Entity\Editor
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create text format with CKEditor enabled.
    $test_format = FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test Format',
      'weight' => 0,
      'filters' => [],
    ]);
    $test_format->save();

    $this->editor = Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor',
    ]);
    $this->editor->save();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'body'),
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])->save();

    $account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'edit own page content',
      'use text format test_format',
    ]);

    $this->drupalLogin($account);
  }

  /**
   * Tests the dcf_table plugin.
   */
  public function testDcfTables() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    // Add Table button.
    $settings = $this->editor->getSettings();
    $settings['toolbar']['rows'][0][] = [
      'name' => 'Table',
      'items' => [
        'Table',
      ],
    ];
    $this->editor->setSettings($settings);
    $this->editor->save();

    // Go to node creation page.
    $this->drupalGet('node/add/page');
    $this->waitForEditor();

    $this->assignNameToCkeditorIframe();

    // Click on the table plugin.
    $this->pressEditorButton('table');
    $web_assert->assertWaitOnAjaxRequest();

    // Save the 'OK' [save] button.
    $page->find('css', 'a.cke_dialog_ui_button_ok')->click();
    $web_assert->assertWaitOnAjaxRequest();

    $this->getSession()->switchToIFrame('ckeditor');

    // Verify dcf-table class is not added to table.
    $table_element = $web_assert->elementExists('css', 'table');
    $this->assertFalse($table_element->hasClass('dcf-table'));

    $this->getSession()->switchToWindow();
    $page->fillField('Title', 'Test Page');
    $page->pressButton('Save');

    // Update settings and enable dcf_table plugin.
    $settings = $this->editor->getSettings();
    $settings['plugins']['dcf_base']['enabled_plugins']['dcf_table'] = 'dcf_table';
    $this->editor->setSettings($settings);
    $this->editor->save();

    // Go to node creation page.
    $this->drupalGet('node/add/page');
    $this->waitForEditor();

    $this->assignNameToCkeditorIframe();

    // Click on the table plugin.
    $this->pressEditorButton('table');
    $web_assert->assertWaitOnAjaxRequest();

    // Save the 'OK' [save] button.
    $page->find('css', 'a.cke_dialog_ui_button_ok')->click();
    $web_assert->assertWaitOnAjaxRequest();

    $this->getSession()->switchToIFrame('ckeditor');

    // Verify dcf-table class is added to table.
    $table_element = $web_assert->elementExists('css', 'table');
    $this->assertTrue($table_element->hasClass('dcf-table'));

    $this->getSession()->switchToWindow();
    $page->fillField('Title', 'Test Page');
    $page->pressButton('Save');

  }

}
