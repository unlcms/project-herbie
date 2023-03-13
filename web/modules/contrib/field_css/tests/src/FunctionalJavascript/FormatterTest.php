<?php

namespace Drupal\Tests\field_css\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests the Field CSS field formatter.
 *
 * @group field_css
 */
class FormatterTest extends TestBase {

  use ContextualLinkClickTrait;

  /**
   * Tests CSS field formatter rendering.
   */
  public function testFieldFormatterRender() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->codeUser);

    $body_value = $this->randomMachineName(32);
    $this->createNode([
      'type' => 'page',
      'body' => [
        'value' => $body_value,
        'format' => filter_default_format(),
      ],
      // An extra space is purposefully included between "color:" and "blue".
      // The asserts below verify its removal, which is a proxy for verifying
      // CSS code is being formatted by OutputFormat::createPretty().
      'field_code' => 'p {' . PHP_EOL . ' color:  blue;' . PHP_EOL . '}',
    ]);

    $this->drupalGet("node/1");

    // By default, <script> is placed in <head>.
    // Verify <script> is placed in <head>'.
    $assert_session->elementExists('xpath', '//head/style[normalize-space(text()) = "p { color: blue; }"]');
    // Verify CSS is being formatted by OutputFormat::createPretty() by
    // verifying extra space is removed. This needs verified separately
    // because Xpath's normalize-space() function removes extra spaces.
    $assert_session->elementExists('xpath', '//head/style[contains(string(), "color: blue;")]');

    // Move <script> element to <body>.
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'page', 'default')
      ->setComponent('field_code', [
        'type' => 'css',
        'settings' => [
          'location' => 'body',
          'prefix' => 'none',
          'fixed_prefix_value' => '',
        ],
      ])
      ->save();

    $this->drupalGet("node/1");

    // Verify <script> is placed in <body> immediately below the body field.'.
    $assert_session->elementExists('xpath', '//body//p[contains(string(), ' . $body_value . ')]/parent::div/following-sibling::style[normalize-space(text()) = "p { color: blue; }"]');
    // Verify CSS is being formatted by OutputFormat::createPretty().
    $assert_session->elementExists('xpath', '//body//p[contains(string(), ' . $body_value . ')]/parent::div/following-sibling::style[contains(string(), "color: blue;")]');

    // Verify fixed value is prefixed.
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'page', 'default')
      ->setComponent('field_code', [
        'type' => 'css',
        'settings' => [
          'location' => 'head',
          'prefix' => 'fixed-value',
          'fixed_prefix_value' => 'grantne',
        ],
      ])
      ->save();

    $this->drupalGet("node/1");

    $assert_session->elementExists('xpath', '//head/style[normalize-space(text()) = ".grantne p { color: blue; }"]');
    // Verify CSS is being formatted by OutputFormat::createPretty().
    $assert_session->elementExists('xpath', '//head/style[contains(string(), "color: blue;")]');

    // Verify entity-item is prefixed.
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'page', 'default')
      ->setComponent('field_code', [
        'type' => 'css',
        'settings' => [
          'location' => 'head',
          'prefix' => 'entity-item',
          'fixed_prefix_value' => '',
        ],
      ])
      ->save();

    $this->drupalGet("node/1");

    $assert_session->elementExists('xpath', '//head/style[normalize-space(text()) = ".scoped-css--node-1 p { color: blue; }"]');
    // Verify CSS is being formatted by OutputFormat::createPretty().
    $assert_session->elementExists('xpath', '//head/style[contains(string(), "color: blue;")]');
  }

  /**
   * Test Layout Builder integration.
   */
  public function testLayoutBuilderIntegration() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    \Drupal::service('module_installer')->install([
      'block_content',
      'contextual',
      'layout_builder',
    ]);

    // Create new role for testing Layout Builder integration and grant to
    // test user.
    $layout_builder_role = $this->drupalCreateRole([
      'access contextual links',
      'administer node display',
      'administer node fields',
      'bypass node access',
      'configure any layout',
    ]);
    $this->codeUser->addRole($layout_builder_role);
    $this->codeUser->save();
    $this->drupalLogin($this->codeUser);

    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic Block',
    ]);
    $block_content_type->save();

    // Create a CSS Code field and attach to basic block type.
    FieldStorageConfig::create([
      'field_name' => 'field_block_code',
      'entity_type' => 'block_content',
      'type' => 'css',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_block_code',
      'entity_type' => 'block_content',
      'bundle' => 'basic',
      'label' => 'CSS Code',
    ])->save();

    $entity_display_repository = \Drupal::service('entity_display.repository');

    $entity_display_repository->getFormDisplay('block_content', 'basic', 'default')
      ->setComponent('field_block_code', [
        'type' => 'css',
      ])
      ->save();

    $entity_display_repository->getViewDisplay('block_content', 'basic', 'default')
      ->setComponent('field_block_code', [
        'type' => 'css',
        'settings' => [
          'location' => 'head',
          'prefix' => 'fixed-value',
          'fixed_prefix_value' => 'grantne',
        ],
      ])
      ->save();

    // And a block content entity.
    $block_content = BlockContent::create([
      'info' => 'Testing Block',
      'type' => 'basic',
      'field_block_code' => 'p { color: yellow; }',
    ]);
    $block_content->save();

    // Enable Layout Builder for page content type.
    $this->drupalGet("admin/structure/types/manage/page/display/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    $this->createNode([
      'type' => 'page',
      'body' => [
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      ],
    ]);

    $this->drupalGet("node/1/layout");

    // Remove CSS Code field from node from layout.
    $this->clickContextualLink('div[data-layout-content-preview-placeholder-label*="CSS Code"]', 'Remove block');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();

    // Add Testing Block custom block.
    $page->clickLink('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Testing Block');
    $assert_session->assertWaitOnAjaxRequest();
    $page->find('xpath', '//div[contains(@class, "ui-dialog-off-canvas")]//input[contains(@class, "form-submit")]')
      ->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Verify <script> is rendered in <body> and not in <head> on node's
    // layout page.
    $assert_session->elementNotExists('xpath', '//head/style[contains(string(), "color: yellow;")]');
    $assert_session->elementExists('xpath', '//body//h2[contains(string(), "Testing Block")]/following-sibling::style[contains(string(), "color: yellow;")]');

    $page->pressButton('Save layout');

    // Verify <script> is rendered in <head> on node's view page.
    $assert_session->elementExists('xpath', '//head/style[contains(string(), "color: yellow;")]');
    $element = $page->find('xpath', '//div[@class="grantne"]');
    $this->assertNotEmpty($element);
    $this->assertContains('Testing Block', $element->getHtml());
  }

  /**
   * Tests the formatter UI and storage.
   */
  public function testFormatterUiStorage() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    \Drupal::service('module_installer')->install([
      'block',
      'field_ui',
    ]);
    $this->drupalPlaceBlock('system_messages_block');

    // Create new role for testing formatter UI and grant to test user.
    $field_ui_role = $this->drupalCreateRole([
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    $this->codeUser->addRole($field_ui_role);
    $this->codeUser->save();
    $this->drupalLogin($this->codeUser);

    $this->drupalGet("admin/structure/types/manage/page/display");

    // Verify default configuration in UI.
    $summary_markup = $this->getSummaryCell()->getHtml();
    $this->assertContains('Location: HEAD', $summary_markup);
    $this->assertContains('Selector Prefix: None', $summary_markup);
    $this->assertNotContains('Fixed Prefix Value', $summary_markup);

    // Verify default configuration in storage.
    $display_component = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'page', 'default')
      ->getComponent('field_code');
    $this->assertSame($display_component['settings']['location'], 'head');
    $this->assertSame($display_component['settings']['prefix'], 'none');
    $this->assertSame($display_component['settings']['fixed_prefix_value'], '');

    // Update formatter settings.
    $page->find('xpath', '//input[@name="field_code_settings_edit"]')->click();
    $assert_session->assertWaitOnAjaxRequest();

    $page->fillField('fields[field_code][settings_edit_form][settings][location]', 'body');
    $page->fillField('Selector Prefix', 'fixed-value');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify Fixed Prefix Value field is conditionally required.
    $assert_session->pageTextContains('A Fixed Prefix Value must be entered.');

    $page->fillField('Fixed Prefix Value', 'venango');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains('A Fixed Prefix Value must be entered.');

    // Verify summary.
    $summary_markup = $this->getSummaryCell()->getHtml();
    $this->assertContains('Location: BODY', $summary_markup);
    $this->assertContains('Selector Prefix: Fixed Value', $summary_markup);
    $this->assertContains('Fixed Prefix Value: venango', $summary_markup);

    $page->pressButton('Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // For some reason it's necessary to wipe out all caching before retrieving
    // the entity display repository again.
    drupal_flush_all_caches();

    // Verify updated configuration in storage.
    $display_component = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'page', 'default')
      ->getComponent('field_code');
    $this->assertSame($display_component['settings']['location'], 'body');
    $this->assertSame($display_component['settings']['prefix'], 'fixed-value');
    $this->assertSame($display_component['settings']['fixed_prefix_value'], 'venango');

    $this->drupalGet("admin/structure/types/manage/page/display");

    // Update formatter settings.
    $page->find('xpath', '//input[@name="field_code_settings_edit"]')->click();
    $assert_session->assertWaitOnAjaxRequest();

    $page->fillField('Fixed Prefix Value', '');
    $page->fillField('Selector Prefix', 'entity-item');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    $assert_session->pageTextNotContains('A Fixed Prefix Value must be entered.');

    $page->pressButton('Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // For some reason it's necessary to wipe out all caching before retrieving
    // the entity display repository again.
    drupal_flush_all_caches();

    // Verify updated configuration in storage.
    $display_component = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'page', 'default')
      ->getComponent('field_code');
    $this->assertSame($display_component['settings']['location'], 'body');
    $this->assertSame($display_component['settings']['prefix'], 'entity-item');
    $this->assertSame($display_component['settings']['fixed_prefix_value'], '');
  }

}
