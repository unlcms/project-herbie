<?php

namespace Drupal\Tests\field_css\FunctionalJavascript;

use Drupal\field_css\Plugin\Field\FieldWidget\CssWidget;

/**
 * Tests the CSS field widget.
 *
 * @group field_css
 */
class WidgetTest extends TestBase {

  /**
   * Tests CSS widget rendering.
   */
  public function testWidgetRendering() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->codeUser);

    $this->drupalGet("node/add/page");

    // Verify CSS Code field is rendered.
    $assert_session->elementExists('xpath', '//textarea[@name="field_code[0][value]"]');

    // Install codemirror_editor module.
    \Drupal::service('module_installer')->install(['codemirror_editor']);

    $this->drupalGet("node/add/page");

    // Verify CSS Code field is rendered with CodeMirror editor.
    $assert_session->elementExists('xpath', '//textarea[@name="field_code[0][value]"]/following-sibling::div[contains(@class, "CodeMirror")]');

    // Verify toolbar is rendered.
    $assert_session->elementExists('xpath', '//div[@class="cme-toolbar"]');

    // Verify buttons are rendered.
    foreach ($this->getAvailableButtons() as $button) {
      $assert_session->elementExists('xpath', "//*[@data-cme-button='" . $button . "']");
    }

    // Alter the widget configuration.
    $buttons_allowed = [
      'enlarge',
      'shrink',
    ];
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.page.default')
      ->setComponent('field_code', [
        'settings' => [
          'toolbar' => TRUE,
          'buttons' => $buttons_allowed,
        ],
      ])
      ->save();

    $this->drupalGet("node/add/page");

    // Verify allowed buttons are rendered.
    foreach ($buttons_allowed as $button) {
      $assert_session->elementExists('xpath', "//*[@data-cme-button='" . $button . "']");
    }
    // Verify disallowed buttons are not rendered.
    foreach (array_diff($this->getAvailableButtons(), $buttons_allowed) as $button) {
      $assert_session->elementNotExists('xpath', "//*[@data-cme-button='" . $button . "']");
    }

    // Alter widget configuration to remove toolbar.
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.page.default')
      ->setComponent('field_code', [
        'settings' => [
          'toolbar' => FALSE,
          'buttons' => [],
        ],
      ])
      ->save();

    $this->drupalGet("node/add/page");

    // Verify toolbar is not rendered.
    $assert_session->elementNotExists('xpath', '//div[@class="cme-toolbar"]');
  }

  /**
   * Tests widget settings form.
   */
  public function testWidgetSettingsForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    \Drupal::service('module_installer')->install([
      'block',
      'field_ui',
    ]);
    $this->drupalPlaceBlock('system_messages_block');

    // Create new role for testing widget UI and grant to test user.
    $field_ui_role = $this->drupalCreateRole([
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    $this->codeUser->addRole($field_ui_role);
    $this->codeUser->save();
    $this->drupalLogin($this->codeUser);

    $this->drupalGet("admin/structure/types/manage/page/form-display");

    // Verify the widget has no settings summary.
    $assert_session->elementNotExists('xpath', '//input[@name="field_code_settings_edit"]');

    \Drupal::service('module_installer')->install([
      'codemirror_editor',
    ]);

    $this->drupalGet("admin/structure/types/manage/page/form-display");

    // Verify default settings in summary.
    $summary_markup = $this->getSummaryCell()->getHtml();
    $this->assertContains('Load toolbar: Yes', $summary_markup);
    $this->assertContains('Toolbar buttons: undo, redo, enlarge, shrink', $summary_markup);

    $page->pressButton('Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // Load and verify form display config.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.page.default')
      ->getComponent('field_code');

    $this->assertSame($form_display['settings']['toolbar'], TRUE);
    $this->assertSame($form_display['settings']['buttons'], $this->getAvailableButtons());

    $this->drupalGet("admin/structure/types/manage/page/form-display");

    $page->find('xpath', '//input[@name="field_code_settings_edit"]')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Remove undo and redo buttons.
    $page->find('xpath', '//select[@name="fields[field_code][settings_edit_form][settings][buttons][]"]/option[@value="undo"]')->click();
    $page->find('xpath', '//select[@name="fields[field_code][settings_edit_form][settings][buttons][]"]/option[@value="redo"]')->click();

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify updated settings in summary.
    $summary_markup = $this->getSummaryCell()->getHtml();
    $this->assertContains('Load toolbar: Yes', $summary_markup);
    $this->assertContains('Toolbar buttons: enlarge, shrink', $summary_markup);

    $page->pressButton('Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // Load and verify updated form display config.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.page.default')
      ->getComponent('field_code');

    $this->assertSame($form_display['settings']['toolbar'], TRUE);
    $this->assertSame($form_display['settings']['buttons'], ['enlarge', 'shrink']);

    $this->drupalGet("admin/structure/types/manage/page/form-display");

    $page->find('xpath', '//input[@name="field_code_settings_edit"]')->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Disable toolbar and verify #states behavior on buttons field.
    $assert_session->elementExists('xpath', '//select[@name="fields[field_code][settings_edit_form][settings][buttons][]"]');
    $page->uncheckField('fields[field_code][settings_edit_form][settings][toolbar]');
    $assert_session->elementNotExists('xpath', '//input[@name="fields[field_code][settings_edit_form][settings][buttons][]"]');

    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify updated settings in summary.
    $summary_markup = $this->getSummaryCell()->getHtml();
    $this->assertContains('Load toolbar: No', $summary_markup);
    $this->assertNotContains('Toolbar buttons:', $summary_markup);

    $page->pressButton('Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // Load and verify updated form display config.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.page.default')
      ->getComponent('field_code');

    $this->assertSame($form_display['settings']['toolbar'], FALSE);
  }

  /**
   * Tests widget validation.
   */
  public function testWidgetValidation() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    \Drupal::service('module_installer')->install([
      'block',
    ]);
    $this->drupalPlaceBlock('system_messages_block');

    $this->drupalLogin($this->codeUser);

    $this->drupalGet("node/add/page");

    $page->fillField('Title', 'A Test Page');
    $page->fillField('CSS Code', ':root p { color: red; }');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The :root selector cannot be used.');

    $page->fillField('CSS Code', 'p { color: red; }');
    $page->pressButton('Save');
    $assert_session->pageTextContains('page A Test Page has been created.');
  }

  /**
   * Tests permission.
   */
  public function testWidgetPermission() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->codeUser);

    $this->drupalGet("node/add/page");

    // Verify user has access to the CSS Code field.
    $assert_session->elementExists('xpath', '//textarea[@name="field_code[0][value]"]');

    $this->drupalLogin($this->authUser);

    $this->drupalGet("node/add/page");

    // Verify user does not have access to the CSS Code field.
    $assert_session->elementNotExists('xpath', '//textarea[@name="field_code[0][value]"]');
  }

  /**
   * Returns an array of available CodeMirror buttons.
   *
   * @return array
   *   An array of available CodeMirror buttons.
   */
  protected function getAvailableButtons() {
    return CssWidget::getAvailableButtons();
  }

}
