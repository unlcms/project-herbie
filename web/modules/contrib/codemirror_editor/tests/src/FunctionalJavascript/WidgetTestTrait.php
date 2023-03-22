<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

use Drupal\codemirror_editor\CodeMirrorPluginTrait;
use Drupal\Component\Utility\Html;

/**
 * Provides a helper methods to for testing widgets.
 */
trait WidgetTestTrait {

  use CodeMirrorPluginTrait;

  /**
   * Test callback.
   */
  public function testWidgetEditor() {
    $page = $this->getSession()->getPage();

    $permissions = [
      'administer node fields',
      'administer node form display',
      'create ' . $this->contentTypeName . ' content',
      'edit any ' . $this->contentTypeName . ' content',
    ];
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    // Default widget settings.
    $widget_settings = [
      'rows' => 5,
      'placeholder' => '',
      'mode' => 'text/html',
      'toolbar' => TRUE,
      'buttons' => $this->getAvailableButtons(),
      'lineWrapping' => FALSE,
      'lineNumbers' => FALSE,
      'foldGutter' => FALSE,
      'autoCloseTags' => TRUE,
      'styleActiveLine' => FALSE,
    ];

    $this->drupalGet('node/add/' . $this->contentTypeName);
    $this->assertWidgetForm($widget_settings);

    $this->drupalGet('admin/structure/types/manage/' . $this->contentTypeName . '/form-display');

    $this->assertWidgetSettingsSummary($widget_settings);

    $this->click('//input[@name = "' . $this->fieldName . '_settings_edit"]');

    $this->assertWidgetSettingsForm($widget_settings);

    $widget_settings = [
      'rows' => 10,
      'placeholder' => 'Example',
      'mode' => 'application/xml',
      'toolbar' => FALSE,
      'buttons' => [],
      'lineWrapping' => TRUE,
      'lineNumbers' => TRUE,
      'foldGutter' => TRUE,
      'autoCloseTags' => FALSE,
      'styleActiveLine' => TRUE,
    ];

    $this->updateWidgetSettingField('rows', $widget_settings['rows']);
    $this->updateWidgetSettingField('placeholder', $widget_settings['placeholder']);
    $this->updateWidgetSettingField('mode', $widget_settings['mode']);
    // Verify buttons select field is visible.
    $element = $page->find('xpath', "//select[@name='fields[$this->fieldName][settings_edit_form][settings][buttons][]']");
    $this->assertTrue($element->isVisible());
    $this->updateWidgetSettingField('toolbar', $widget_settings['toolbar']);
    // Verify buttons select is not longer visible.
    $this->assertFalse($element->isVisible());
    $this->updateWidgetSettingField('lineWrapping', $widget_settings['lineWrapping']);
    $this->updateWidgetSettingField('lineNumbers', $widget_settings['lineNumbers']);
    $this->updateWidgetSettingField('foldGutter', $widget_settings['foldGutter']);
    $this->updateWidgetSettingField('autoCloseTags', $widget_settings['autoCloseTags']);
    $this->updateWidgetSettingField('styleActiveLine', $widget_settings['styleActiveLine']);

    $page = $this->getSession()->getPage();
    $page->pressButton($this->fieldName . '_plugin_settings_update');

    $this->assertSession()
      ->waitForElementVisible('xpath', '//select[@name = "fields[' . $this->fieldName . '][type]"]');

    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertWidgetSettingsSummary($widget_settings);

    $this->drupalGet('node/add/' . $this->contentTypeName);
    $this->assertWidgetForm($widget_settings);
    $page->fillField('Title', 'Example');
    $this->editorSetValue('It works!');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('It works!');

    $this->drupalGet('node/1/edit');
    $this->assertEditorValue('It works!');
  }

  /**
   * Asserts widget form.
   */
  protected function assertWidgetForm(array $widget_settings) {
    $xpath = sprintf(
      '//textarea[@name = "' . $this->fieldName . '[0][value]" and @rows = %d and @placeholder = "%s"]',
      $widget_settings['rows'],
      $widget_settings['placeholder']
    );
    $this->assertSession()->elementExists('xpath', $xpath);
    $widget_settings['toolbar'] ? $this->assertToolbarExists() : $this->assertToolbarNotExists();
    // 'buttons' is not available from cm.getOption().
    if (!empty($widget_settings['buttons'])) {
      foreach ($widget_settings['buttons'] as $button) {
        $this->assertSession()->elementExists('xpath', "//*[@data-cme-button='" . $button . "']");
      }
    }
    $this->assertEditorOption('mode', $widget_settings['mode']);
    $this->assertEditorOption('lineWrapping', $widget_settings['lineWrapping']);
    $this->assertEditorOption('lineNumbers', $widget_settings['lineNumbers']);
    $this->assertEditorOption('foldGutter', $widget_settings['foldGutter']);
    $this->assertEditorOption('autoCloseTags', $widget_settings['autoCloseTags']);
    $this->assertEditorOption('styleActiveLine', $widget_settings['styleActiveLine']);
  }

  /**
   * Asserts widget settings summary.
   */
  protected function assertWidgetSettingsSummary(array $widget_settings) {
    $expected_summary[] = 'Number of rows: ' . $widget_settings['rows'];
    if ($widget_settings['placeholder']) {
      $expected_summary[] = 'Placeholder: ' . $widget_settings['placeholder'];
    }
    $expected_summary[] = 'Language mode: ' . $widget_settings['mode'];
    $expected_summary[] = 'Load toolbar: ' . ($widget_settings['toolbar'] ? 'Yes' : 'No');
    if ($widget_settings['toolbar']) {
      $expected_summary[] = 'Toolbar buttons: ' . implode(', ', $widget_settings['buttons']);
    }
    $expected_summary[] = 'Line wrapping: ' . ($widget_settings['lineWrapping'] ? 'Yes' : 'No');
    $expected_summary[] = 'Line numbers: ' . ($widget_settings['lineNumbers'] ? 'Yes' : 'No');
    $expected_summary[] = 'Fold gutter: ' . ($widget_settings['foldGutter'] ? 'Yes' : 'No');
    $expected_summary[] = 'Auto close tags: ' . ($widget_settings['autoCloseTags'] ? 'Yes' : 'No');
    $expected_summary[] = 'Style active line: ' . ($widget_settings['styleActiveLine'] ? 'Yes' : 'No');

    $summary_xpath = '//tr[@id = "' . Html::getClass($this->fieldName) . '"]//div[@class = "field-plugin-summary"]';
    $summary = $this->xpath($summary_xpath)[0]->getHtml();

    self::assertEquals(implode('<br>', $expected_summary), $summary);
  }

  /**
   * Asserts widget settings form.
   */
  protected function assertWidgetSettingsForm(array $widget_settings) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $settings_wrapper = $assert_session
      ->waitForElementVisible('xpath', '//div[@data-drupal-selector = "edit-fields-' . Html::getClass($this->fieldName) . '-settings-edit-form"]');

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][rows]" and @value = %d]';
    $xpath = sprintf($xpath, $widget_settings['rows']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][placeholder]" and @value = "%s"]';
    $xpath = sprintf($xpath, $widget_settings['placeholder']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//select[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][mode]"]/optgroup/option[@value = "%s" and @selected]';
    $xpath = sprintf($xpath, $widget_settings['mode']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][toolbar]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['toolbar'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $element = $page->find('xpath', "//select[@name='fields[$this->fieldName][settings_edit_form][settings][buttons][]']");
    $this->assertNotEmpty($element);
    self::assertSame($widget_settings['buttons'], $element->getValue());

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][lineWrapping]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['lineWrapping'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][lineNumbers]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['lineNumbers'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][foldGutter]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['foldGutter'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][autoCloseTags]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['autoCloseTags'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[' . $this->fieldName . '][settings_edit_form][settings][styleActiveLine]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['styleActiveLine'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);
  }

  /**
   * Sets a value for a given settings field.
   */
  protected function updateWidgetSettingField($name, $value) {
    $page = $this->getSession()->getPage();
    $field_name = "fields[$this->fieldName][settings_edit_form][settings][$name]";
    if (is_bool($value)) {
      $value ? $page->checkField($field_name) : $page->uncheckField($field_name);
    }
    else {
      $page->fillField($field_name, $value);
    }
  }

}
