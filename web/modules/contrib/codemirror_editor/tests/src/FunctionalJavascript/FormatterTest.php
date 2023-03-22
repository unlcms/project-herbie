<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Tests the CodeMirror field formatter.
 *
 * @group codemirror_editor
 */
final class FormatterTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->createNode([
      'type' => 'test',
      'field_code' => '<b class="bold">Example</b>',
    ]);
  }

  /**
   * Test callback.
   */
  public function testFormatter() {

    $permissions = [
      'administer node fields',
      'administer node display',
      'create test content',
      'edit any test content',
    ];
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    // Default formatter settings.
    $formatter_settings = [
      'mode' => 'text/html',
      'lineWrapping' => TRUE,
      'lineNumbers' => TRUE,
      'foldGutter' => FALSE,
    ];

    $this->drupalGet('node/1');
    $this->assertFormatter($formatter_settings);

    $this->drupalGet('admin/structure/types/manage/test/display');

    $this->assertFormatterSettingsSummary($formatter_settings);

    $this->click('//input[@name = "field_code_settings_edit"]');

    $this->assertFormatterSettingsForm($formatter_settings);

    $formatter_settings = [
      'mode' => 'application/xml',
      'lineWrapping' => FALSE,
      'lineNumbers' => FALSE,
      'foldGutter' => TRUE,
    ];

    $this->updateFormatterSettingField('mode', $formatter_settings['mode']);
    $this->updateFormatterSettingField('lineWrapping', $formatter_settings['lineWrapping']);
    $this->updateFormatterSettingField('lineNumbers', $formatter_settings['lineNumbers']);
    $this->updateFormatterSettingField('foldGutter', $formatter_settings['foldGutter']);

    $page = $this->getSession()->getPage();
    $page->pressButton('field_code_plugin_settings_update');

    $this->assertSession()
      ->waitForElementVisible('xpath', '//select[@name = "fields[field_code][type]"]');

    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertFormatterSettingsSummary($formatter_settings);

    $this->drupalGet('node/1');
    $this->assertFormatter($formatter_settings);
  }

  /**
   * Asserts formatter's output.
   */
  protected function assertFormatter(array $formatter_settings) {
    $this->assertEditorOption('mode', $formatter_settings['mode']);
    $this->assertEditorOption('lineWrapping', $formatter_settings['lineWrapping']);
    $this->assertEditorOption('lineNumbers', $formatter_settings['lineNumbers']);
    $this->assertEditorOption('foldGutter', $formatter_settings['foldGutter']);
  }

  /**
   * Asserts formatter settings summary.
   */
  protected function assertFormatterSettingsSummary(array $formatter_settings) {
    $expected_summary[] = 'Language mode: ' . $formatter_settings['mode'];
    $expected_summary[] = 'Line wrapping: ' . ($formatter_settings['lineWrapping'] ? 'Yes' : 'No');
    $expected_summary[] = 'Line numbers: ' . ($formatter_settings['lineNumbers'] ? 'Yes' : 'No');
    $expected_summary[] = 'Fold gutter: ' . ($formatter_settings['foldGutter'] ? 'Yes' : 'No');

    $summary_xpath = '//tr[@id = "field-code"]//div[@class = "field-plugin-summary"]';
    $summary = $this->xpath($summary_xpath)[0]->getHtml();

    self::assertEquals(implode('<br>', $expected_summary), $summary);
  }

  /**
   * Asserts formatter settings form.
   */
  protected function assertFormatterSettingsForm(array $formatter_settings) {
    $assert_session = $this->assertSession();

    $settings_wrapper = $assert_session
      ->waitForElementVisible('xpath', '//div[@data-drupal-selector = "edit-fields-field-code-settings-edit-form"]');

    $xpath = '//select[@name = "fields[field_code][settings_edit_form][settings][mode]"]/optgroup/option[@value = "text/html" and @selected]';
    $xpath = sprintf($xpath, $formatter_settings['mode']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_code][settings_edit_form][settings][lineWrapping]" and %s]';
    $xpath = sprintf($xpath, $formatter_settings['lineWrapping'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_code][settings_edit_form][settings][lineNumbers]" and %s]';
    $xpath = sprintf($xpath, $formatter_settings['lineNumbers'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_code][settings_edit_form][settings][foldGutter]" and %s]';
    $xpath = sprintf($xpath, $formatter_settings['foldGutter'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);
  }

  /**
   * Sets a value for a given settings field.
   */
  protected function updateFormatterSettingField($name, $value) {
    $page = $this->getSession()->getPage();
    $field_name = "fields[field_code][settings_edit_form][settings][$name]";
    if (is_bool($value)) {
      $value ? $page->checkField($field_name) : $page->uncheckField($field_name);
    }
    else {
      $page->selectFieldOption($field_name, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getWrapperSelector() {
    return '.cme-wrapper';
  }

}
