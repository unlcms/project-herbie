<?php

namespace Drupal\Tests\layout_builder_component_attributes\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests UI and rendering of component attributes.
 *
 * @group layout_builder_component_attributes
 */
class ComponentAttributeTest extends WebDriverTestBase {

  use BlockCreationTrait;

  use ContextualLinkClickTrait {
    clickContextualLink as protected contextualLinkClickTraitClickContextualLink;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Path prefix for the field UI for the test bundle.
   *
   * @var string
   */
  const FIELD_UI_PREFIX = 'admin/structure/types/manage/bundle_with_section_field';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'layout_builder',
    'layout_builder_component_attributes',
    'layout_builder_component_attributes_test',
    'node',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->createContentType(['type' => 'bundle_with_section_field']);

    // Create an authenticated user.
    $this->auth_user = $this
      ->drupalCreateUser([
        'access administration pages',
        'access contextual links',
        'administer node display',
        'administer node fields',
        'configure any layout',
      ]);

    // Create an admin user.
    $this->admin_user = $this
      ->drupalCreateUser([
        'access administration pages',
        'access contextual links',
        'administer node display',
        'administer node fields',
        'bypass node access',
        'configure any layout',
        'manage layout builder component attributes',
      ]);
    $this->drupalLogin($this->admin_user);

    // Enable layout builder.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default');
    $this->submitForm(
      ['layout[enabled]' => TRUE],
      'Save'
    );

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests permissions are enforced.
   */
  public function testManageComponentAttributesFormPermissions() {
    $this->getSession()->resizeWindow(1200, 2000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->auth_user);

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->resetLayoutBuilderLayout();

    $this->assertNotEmpty($page->findAll('xpath', '//*[contains(@class, "layout-builder-block")]//ul[contains(@class, "contextual-links")]', 'Contextual links are rendered.'));
    $this->assertEmpty($page->findAll('xpath', '//*[contains(@class, "layout-builder-block")]//ul[contains(@class, "contextual-links")]//a[contains(text(), "Manage attributes")]', 'Manage attributes link is not rendered.'));

    $this->drupalLogin($this->admin_user);

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    // Wait for contextual links to load.
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertNotEmpty($page->findAll('xpath', '//*[contains(@class, "layout-builder-block")]//ul[contains(@class, "contextual-links")]//a[contains(text(), "Manage attributes")]', 'Manage attributes link is rendered'));
  }

  /**
   * Tests Manage Component Attributes Form.
   */
  public function testManageComponentAttributesForm() {
    $this->getSession()->resizeWindow(1200, 2000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->resetLayoutBuilderLayout();
    $this->clickContextualLink('.layout-builder-block', 'Manage attributes');
    $assert_session->assertWaitOnAjaxRequest();

    // Test validation, one field at a time.
    // Block Attributes.
    $page->find('xpath', '//details[contains(@id, "edit-block-attributes")]')->click();
    $page->fillField('block_attributes[id]', '(block-id-test');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Element ID must be a valid CSS ID');
    $page->fillField('block_attributes[id]', 'block-id-test');

    $page->fillField('block_attributes[class]', '*block-class1 block-class2');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Classes must be valid CSS classes');
    $page->fillField('block_attributes[class]', 'block-class1 block-class2');

    $page->fillField('block_attributes[style]', 'color blue;');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Inline styles must be valid CSS');
    $page->fillField('block_attributes[style]', 'color: blue;');

    $page->fillField('block_attributes[data]', 'data-block-test' . PHP_EOL . 'ata-block-test2|test-value');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Data attributes must being with "data-"');
    $page->fillField('block_attributes[data]', 'data-block-test' . PHP_EOL . 'data-block-test2|test-value');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    // Block Title Attributes.
    $this->clickContextualLink('.layout-builder-block', 'Manage attributes');
    $assert_session->assertWaitOnAjaxRequest();

    $page->find('xpath', '//details[contains(@id, "edit-block-title-attributes")]')->click();
    $page->fillField('block_title_attributes[id]', '(block-title-id-test');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Element ID must be a valid CSS ID');
    $page->fillField('block_title_attributes[id]', 'block-title-id-test');

    $page->fillField('block_title_attributes[class]', '*block-title-class1 block-title-class2');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Classes must be valid CSS classes');
    $page->fillField('block_title_attributes[class]', 'block-title-class1 block-title-class2');

    $page->fillField('block_title_attributes[style]', 'color white;');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Inline styles must be valid CSS');
    $page->fillField('block_title_attributes[style]', 'color: white;');

    $page->fillField('block_title_attributes[data]', 'data-block-title-test' . PHP_EOL . 'ata-block-title-test2|test-value-title');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Data attributes must being with "data-"');
    $page->fillField('block_title_attributes[data]', 'data-block-title-test' . PHP_EOL . 'data-block-title-test2|test-value-title');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    // Block Content Attributes.
    $this->clickContextualLink('.layout-builder-block', 'Manage attributes');
    $assert_session->assertWaitOnAjaxRequest();

    $page->find('xpath', '//details[contains(@id, "edit-block-content-attributes")]')->click();
    $page->fillField('block_content_attributes[id]', '(block-content-id-test');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Element ID must be a valid CSS ID');
    $page->fillField('block_content_attributes[id]', 'block-content-id-test');

    $page->fillField('block_content_attributes[class]', '*block-content-class1 block-content-class2');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Classes must be valid CSS classes');
    $page->fillField('block_content_attributes[class]', 'block-content-class1 block-content-class2');

    $page->fillField('block_content_attributes[style]', 'color red;');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Inline styles must be valid CSS');
    $page->fillField('block_content_attributes[style]', 'color: red;');

    $page->fillField('block_content_attributes[data]', 'data-block-content-test' . PHP_EOL . 'ata-block-content-test2|test-value-content');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertSettingsTrayValidationMessage('Data attributes must being with "data-"');
    $page->fillField('block_content_attributes[data]', 'data-block-content-test' . PHP_EOL . 'data-block-content-test2|test-value-content');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    $page->pressButton('Save layout');

    // Verify correct rendering of attributes.
    $this->drupalGet('node/add/bundle_with_section_field');
    $page->fillField('Title', 'Test Node Title');
    $page->pressButton('Save');

    $this->drupalGet('node/1');

    // Verify Block Attributes.
    $element = $page->find('xpath', '//*[@id="block-id-test"]');
    $this->assertNotEmpty($element, "Block ID rendered");
    $this->assertTrue($element->hasClass('block-class1'), "Block class rendered");
    $this->assertTrue($element->hasClass('block-class2'), "Block class rendered");
    $style = $element->getAttribute('style');
    $this->assertSame('color: blue;', $style, "Style attribute rendered");
    $this->assertTrue($element->hasAttribute('data-block-test'), "Data attribute rendered");
    $data1 = $element->getAttribute('data-block-test');
    $this->assertEmpty($data1, "Data attribute has no value");
    $this->assertTrue($element->hasAttribute('data-block-test2'), "Data attribute rendered");
    $data2 = $element->getAttribute('data-block-test2');
    $this->assertSame($data2, 'test-value', "Data attribute has expected value");

    // Verify Block Title Attributes.
    $element = $page->find('xpath', '//*[@id="block-title-id-test"]');
    $this->assertNotEmpty($element, "Block ID rendered");
    $this->assertTrue($element->hasClass('block-title-class1'), "Block class rendered");
    $this->assertTrue($element->hasClass('block-title-class2'), "Block class rendered");
    $style = $element->getAttribute('style');
    $this->assertSame('color: white;', $style, "Style attribute rendered");
    $this->assertTrue($element->hasAttribute('data-block-title-test'), "Data attribute rendered");
    $data1 = $element->getAttribute('data-block-title-test');
    $this->assertEmpty($data1, "Data attribute has no value");
    $this->assertTrue($element->hasAttribute('data-block-title-test2'), "Data attribute rendered");
    $data2 = $element->getAttribute('data-block-title-test2');
    $this->assertSame($data2, 'test-value-title', "Data attribute has expected value");

    // Verify Block Content Attributes.
    $element = $page->find('xpath', '//*[@id="block-content-id-test"]');
    $this->assertNotEmpty($element, "Block ID rendered");
    $this->assertTrue($element->hasClass('block-content-class1'), "Block class rendered");
    $this->assertTrue($element->hasClass('block-content-class2'), "Block class rendered");
    $style = $element->getAttribute('style');
    $this->assertSame('color: red;', $style, "Style attribute rendered");
    $this->assertTrue($element->hasAttribute('data-block-content-test'), "Data attribute rendered");
    $data1 = $element->getAttribute('data-block-content-test');
    $this->assertEmpty($data1, "Data attribute has no value");
    $this->assertTrue($element->hasAttribute('data-block-content-test2'), "Data attribute rendered");
    $data2 = $element->getAttribute('data-block-content-test2');
    $this->assertSame($data2, 'test-value-content', "Data attribute has expected value");
  }

  /**
   * Tests allowed attributes (both form render and page render).
   */
  public function testAllowedAttributes() {
    $this->getSession()->resizeWindow(1200, 2000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Initially, populate all fields. This also verifies they are rendered.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->resetLayoutBuilderLayout();
    $this->clickContextualLink('.layout-builder-block', 'Manage attributes');
    $assert_session->assertWaitOnAjaxRequest();

    $page->find('xpath', '//details[contains(@id, "edit-block-attributes")]')->click();
    $page->fillField('block_attributes[id]', 'block-id-test');
    $page->fillField('block_attributes[class]', 'block-class-test');
    $page->fillField('block_attributes[style]', 'color: blue;');
    $page->fillField('block_attributes[data]', 'data-block-test|test-value');

    $page->find('xpath', '//details[contains(@id, "edit-block-title-attributes")]')->click();
    $page->fillField('block_title_attributes[id]', 'block-title-id-test');
    $page->fillField('block_title_attributes[class]', 'block-title-class-test');
    $page->fillField('block_title_attributes[style]', 'color: white;');
    $page->fillField('block_title_attributes[data]', 'data-block-title-test|test-value-title');

    $page->find('xpath', '//details[contains(@id, "edit-block-content-attributes")]')->click();
    $page->fillField('block_content_attributes[id]', 'block-content-id-test');
    $page->fillField('block_content_attributes[class]', 'block-content-class-test');
    $page->fillField('block_content_attributes[style]', 'color: red;');
    $page->fillField('block_content_attributes[data]', 'data-block-content-test|test-value-content');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    $page->pressButton('Save layout');

    // Verify Block Attributes.
    $attributes = [
      'id' => 'block-id-test',
      'class' => 'block-class-test',
      'style' => 'color: blue;',
      'data-block-test' => 'test-value',
    ];
    $this->verifyAllowedAttributes('block_attributes', $attributes);

    // Verify Block Title Attributes.
    $attributes = [
      'id' => 'block-title-id-test',
      'class' => 'block-title-class-test',
      'style' => 'color: white;',
      'data-block-title-test' => 'test-value-title',
    ];
    $this->verifyAllowedAttributes('block_title_attributes', $attributes);

    // Verify Block Content Attributes.
    $attributes = [
      'id' => 'block-content-id-test',
      'class' => 'block-content-class-test',
      'style' => 'color: red;',
      'data-block-content-test' => 'test-value-content',
    ];
    $this->verifyAllowedAttributes('block_content_attributes', $attributes);
  }

  /**
   * Verifies form rendering and on-page rendering of allowed attributes.
   *
   * @param string $group
   *   A group of fields: 'block_attributes', 'block_title_attributes',
   *   or 'block_content_attributes'.
   * @param array $attributes
   *   An array of attributes with the attribute name as the key and a test
   *   value as the value. Only one data-* attribute can be passed per group.
   */
  private function verifyAllowedAttributes($group, array $attributes) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Create an array to keep track of attributes' statuses during test loops.
    // Initially, set all attributes as allowed.
    $attribute_fields = [];
    foreach ($attributes as $attribute => $test_value) {
      // Replace 'data-*' attribute with 'data' to match expected FAPI key.
      if (substr($attribute, 0, 5) === "data-") {
        $attribute = 'data';
      }
      $attribute_fields[$attribute] = TRUE;
    }

    // Load config.
    $config = \Drupal::service('config.factory')->getEditable('layout_builder_component_attributes.settings');

    // Load contextual menu and observe all fields are rendered.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->clickContextualLink('.layout-builder-block', 'Manage attributes');
    $assert_session->assertWaitOnAjaxRequest();

    // Loop through fields.
    foreach ($attribute_fields as $attribute => $attribute_status) {
      $this->assertTrue($page->hasField($group . '[' . $attribute . ']'), "Attribute field " . $attribute . " is rendered for " . $group . " group");
    }

    // Loop through attributes and disable one attribute per time.
    foreach ($attribute_fields as $attribute => $attribute_status) {
      $attribute_fields[$attribute] = FALSE;
      $config->set('allowed_' . $group, $attribute_fields)->save();

      $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
      $this->clickContextualLink('.layout-builder-block', 'Manage attributes');
      $assert_session->assertWaitOnAjaxRequest();

      // Verify only fields for allowed attributes are rendered.
      foreach ($attribute_fields as $attribute_inner => $attribute_status) {
        if ($attribute_fields[$attribute_inner]) {
          $this->assertTrue($page->hasField($group . '[' . $attribute_inner . ']'), "Attribute field " . $attribute_inner . " is rendered for " . $group . " group");
        }
        else {
          $this->assertFalse($page->hasField($group . '[' . $attribute_inner . ']'), "Attribute field " . $attribute_inner . " is not rendered for " . $group . " group");
        }
      }

      // Create and load a test node.
      $page->pressButton('Update');
      $assert_session->assertWaitOnAjaxRequest();

      $page->pressButton('Save layout');

      $this->drupalGet('node/add/bundle_with_section_field');
      $page->fillField('Title', 'Test Node Title');
      $page->pressButton('Save');

      $this->drupalGet('node/1');

      // Load page and verify only allowed attributes are rendered in markup.
      foreach ($attributes as $attribute_inner => $test_value) {
        // Replace 'data-*' attribute with 'data' to match expected FAPI key.
        $attribute_field = (substr($attribute_inner, 0, 5) === "data-") ? 'data' : $attribute_inner;

        if ($attribute_fields[$attribute_field]) {
          $element = $page->find('xpath', '//*[contains(@' . $attribute_inner . ', "' . $test_value . '")]');
          $this->assertNotEmpty($element, "Attribute " . $attribute_inner . " rendered in " . $group . " group");
        }
        else {
          $element = $page->find('xpath', '//*[contains(@' . $attribute_inner . ', "' . $test_value . '")]');
          $this->assertEmpty($element, "Attribute " . $attribute_inner . " not rendered in " . $group . " group");
        }
      }
    }
    // After last loop, verify details element is no longer rendered.
    $element = $page->find('xpath', '//details[contains(@id, "edit-' . $group . '")]');
    $this->assertEmpty($element, "Details element not rendered");
  }

  /**
   * Helper method to assert the settings tray is open.
   *
   * @param string $message
   *   The expected validation message.
   */
  private function assertSettingsTrayValidationMessage($message = '') {
    $page = $this->getSession()->getPage();

    $element = $page->find('xpath', '//form[contains(@id, "layout-builder-manage-attributes-form")]');
    $this->assertStringContainsString($message, $element->getText(), "Validation message found: " . $message);
  }

  /**
   * Helper method to reset a Layout Builder page.
   *
   * This method removes the default section and blocks before creating a new
   * section and adding a single block, which simplifies testing.
   */
  private function resetLayoutBuilderLayout() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $page->clickLink('Remove Section 1');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas input[value="Remove"]');
    $page->pressButton('Remove');
    $assert_session->waitForElementRemoved('css', '#drupal-off-canvas');

    // Assert that there are no sections on the page.
    $assert_session->pageTextNotContains('Remove Section 1');
    $assert_session->pageTextNotContains('Add block');

    // Add back a section and a component.
    $page->clickLink('Add section');
    $assert_session->waitForElement('css', '#drupal-off-canvas .layout-selection');
    $page->clickLink('One column');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas .layout-builder-configure-section input[value="Add section"]');
    $page->pressButton('Add section');
    $assert_session->waitForElementRemoved('css', '#drupal-off-canvas');
    $page->clickLink('Add block');
    $assert_session->waitForElement('css', '#drupal-off-canvas .block-categories');
    $page->clickLink('Powered by Drupal');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas input[name="settings[label_display]"]');
    $page->checkField('Display title');
    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();
  }

  /**
   * Extends ContextualLinkClickTrait::clickContextualLink.
   *
   * @param string $selector
   *   The selector for the element that contains the contextual link.
   * @param string $link_locator
   *   The link id, title, or text.
   * @param bool $force_visible
   *   If true then the button will be forced to visible so it can be clicked.
   */
  protected function clickContextualLink($selector, $link_locator, $force_visible = TRUE) {
    $page = $this->getSession()->getPage();

    // Beginning in Drupal 10, it is necessary to focus the contextual link
    // element before executing clickContextualLink().
    $page->find('css', "{$selector} .contextual .trigger")->focus();
    $this->contextualLinkClickTraitClickContextualLink($selector, $link_locator, $force_visible);
  }

}
