<?php

namespace Drupal\Tests\dcf_layouts\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Demonstrate that DCF Layouts configuration is correctly implemented.
 *
 * @group dcf_layouts
 */
class DcfLayoutsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'dcf_layouts',
    'dcf_layouts_test',
    'layout_builder',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node bundle.
    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer node display',
      'administer node fields',
      'configure any layout',
      'bypass node access',
    ]));
  }

  /**
   * Tests layouts configuration implementation.
   */
  public function testLayouts() {
    $this->getSession()->resizeWindow(1200, 2000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set default layout to be managed with Layout Builder.
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    // Checking is_enable will show allow_custom.
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    // Create test node.
    $this->drupalGet('node/add/bundle_with_section_field');
    $page->fillField('Title', 'Test Node Title');
    $page->pressButton('Save');

    $this->drupalGet('node/1/layout');

    $this->clickLink('Add section');

    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Two column (DCF)');
    $assert_session->assertWaitOnAjaxRequest();

    $page->fillField('layout_settings[title]', 'Section Test Title');

    $assert_session->assertWaitOnAjaxRequest();
    // Verify Title Classes field is not visible.
    $element = $page->find('xpath', '//*[contains(@class, "form-item-layout-settings-title-classes")]');
    $this->assertFalse($element->isVisible());

    $page->checkField('layout_settings[title_display]');

    // Verify Title Classes field is visible.
    $element = $page->find('xpath', '//*[contains(@class, "form-item-layout-settings-title-classes")]');
    $this->assertTrue($element->isVisible());

    $page->fillField('Title classes', 'dcf-lowercase');
    $page->pressButton('Advanced');
    $page->fillField('Section element ID', 'section-id-invalid&*(');
    $page->pressButton('Add section');

    $assert_session->assertWaitOnAjaxRequest();
    // Due to https://www.drupal.org/project/drupal/issues/2897377, validation
    // in the settings tray fails silently, so check that the form did not
    // submit and close instead of checking for the error message.
    $element = $page->find('xpath', '//*[@name="layout_settings[advanced][section_element_id]"]');
    $this->assertNotNull($element);
    $page->fillField('Section element ID', 'section-id-1');
    $page->pressButton('Add section');

    $this->waitForNoElement('#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');

    $this->drupalGet('node/1/layout');

    // Add a block in first section.
    $this->clickLink('Add block');

    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Powered by Drupal');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');

    // Add another block in first section.
    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Add block');

    $assert_session->assertWaitOnAjaxRequest();
    $this->clickLink('Content type');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');

    $this->waitForNoElement('#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');

    // Verify heading class is applied and section title is printed.
    $element = $page->find('xpath', '//*[@class="dcf-lowercase"]');
    $this->assertSame($element->getText(), 'Section Test Title');
    $this->assertFalse($element->hasClass('dcf-uppercase'));

    // Verify section class is printed.
    $element = $page->find('xpath', '//*[@class="section-class-1"]');
    $this->assertNotEmpty($element);
    $this->assertFalse($element->hasClass('section-class-2'));

    // Verify margin-top is only added to second block.
    $element = $page->find('xpath', '//*[contains(@class, "block-system-powered-by-block")]');
    $this->assertFalse($element->hasClass('dcf-mt-1'));

    $element = $page->find('xpath', '//*[contains(@class, "block-field-blocknodebundle-with-section-fieldtype")]');
    $this->assertTrue($element->hasClass('dcf-mt-1'));

    // Verify section id is printed.
    $element = $page->find('xpath', '//*[@id="section-id-1"]');
    $this->assertNotEmpty($element);

    $this->drupalGet('node/1/layout');

    // Update section to hide section title from rendering.
    $this->clickLink('Configure Section Test Title');
    $assert_session->assertWaitOnAjaxRequest();
    $page->uncheckField('layout_settings[title_display]');

    $page->pressButton('Update');

    $this->waitForNoElement('#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');

    $assert_session->pageTextNotContains('Section Test Title');
  }

  /**
   * Waits for an element to be removed from the page.
   *
   * @param string $selector
   *   CSS selector.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   *
   * @todo Remove in https://www.drupal.org/node/2892440.
   */
  protected function waitForNoElement($selector, $timeout = 10000) {
    $condition = "(typeof jQuery !== 'undefined' && jQuery('$selector').length === 0)";
    $this->assertJsCondition($condition, $timeout);
  }

}
