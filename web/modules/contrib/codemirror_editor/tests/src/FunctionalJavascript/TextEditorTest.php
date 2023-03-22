<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

use Drupal\codemirror_editor\CodeMirrorPluginTrait;

/**
 * Tests the CodeMirror text editor.
 *
 * @group codemirror_editor
 */
final class TextEditorTest extends TestBase {

  use CodeMirrorPluginTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->createNode([
      'title' => 'Example',
      'body' => [
        'value' => 'Test',
        'format' => 'codemirror',
      ],
    ]);
  }

  /**
   * Test callback.
   */
  public function testTextEditor() {

    $permissions = [
      'administer filters',
      'edit any page content',
      'use text format codemirror',
      'use text format basic',
    ];
    $user = $this->drupalCreateUser($permissions);

    $this->drupalLogin($user);

    $this->drupalGet('node/1/edit');

    $this->editorSetValue('Test');
    $this->editorSetSelection([0, 0], [0, 4]);
    $this->editorClickButton('bold');
    $this->assertEditorValue('<strong>Test</strong>');

    $this->assertToolbarExists();
    // 'buttons' is not available from cm.getOption().
    $buttons = $this->getAvailableButtons();
    foreach ($buttons as $button) {
      $this->assertSession()->elementExists('xpath', "//*[@data-cme-button='" . $button . "']");
    }
    $this->assertEditorOption('mode', 'text/html');
    $this->assertEditorOption('lineWrapping', FALSE);
    $this->assertEditorOption('lineNumbers', FALSE);
    $this->assertEditorOption('foldGutter', FALSE);
    $this->assertEditorOption('autoCloseTags', TRUE);
    $this->assertEditorOption('styleActiveLine', FALSE);

    // Test if the editor is correctly attached and detached.
    $this->assertElementExist('//div[contains(@class, "CodeMirror")]');
    $this->setBodyFormat('basic');
    $this->assertElementNotExist('//div[contains(@class, "CodeMirror")]');
    $this->setBodyFormat('codemirror');
    $this->assertElementExist('//div[contains(@class, "CodeMirror")]');

    $this->drupalGet('admin/config/content/formats/manage/codemirror');

    // Make sure that the form displays default values.
    $this->assertElementExist('//select[@name = "editor[settings][mode]"]/optgroup/option[@value = "text/html" and @selected]');
    foreach ($buttons as $button) {
      $this->assertElementExist('//select[@name = "editor[settings][buttons][]"]/option[@value = "' . $button . '" and @selected]');
    }
    $this->assertElementExist('//input[@name = "editor[settings][toolbar]" and @checked]');
    $this->assertElementExist('//input[@name = "editor[settings][lineWrapping]" and not(@checked)]');
    $this->assertElementExist('//input[@name = "editor[settings][lineNumbers]" and not(@checked)]');
    $this->assertElementExist('//input[@name = "editor[settings][foldGutter]" and not(@checked)]');
    $this->assertElementExist('//input[@name = "editor[settings][autoCloseTags]" and @checked]');
    $this->assertElementExist('//input[@name = "editor[settings][styleActiveLine]" and not(@checked)]');

    $this->scrollToBottom();
    $edit = [
      'editor[settings][mode]' => 'application/xml',
      'editor[settings][toolbar]' => FALSE,
      'editor[settings][lineWrapping]' => TRUE,
      'editor[settings][lineNumbers]' => TRUE,
      'editor[settings][foldGutter]' => TRUE,
      'editor[settings][autoCloseTags]' => FALSE,
      'editor[settings][styleActiveLine]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('node/1/edit');

    $this->assertToolbarNotExists();
    $this->assertElementNotExist('//select[@name = "editor[settings][buttons][]"]');
    $this->assertEditorOption('mode', 'application/xml');
    $this->assertEditorOption('lineWrapping', TRUE);
    $this->assertEditorOption('lineNumbers', TRUE);
    $this->assertEditorOption('foldGutter', TRUE);
    $this->assertEditorOption('autoCloseTags', FALSE);
    $this->assertEditorOption('styleActiveLine', TRUE);

    // Update buttons config and verify correct rendering on toolbar.
    $this->drupalGet('admin/config/content/formats/manage/codemirror');

    $buttons_allowed = [
      'bold',
      'italic',
    ];
    $buttons_disallowed = array_diff($buttons_allowed, $buttons);

    $this->scrollToBottom();
    $edit = [
      'editor[settings][toolbar]' => TRUE,
      'editor[settings][buttons][]' => $buttons_allowed,
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('node/1/edit');

    $this->assertToolbarExists();
    foreach ($buttons_allowed as $button) {
      $this->assertSession()->elementExists('xpath', "//*[@data-cme-button='" . $button . "']");
    }
    foreach ($buttons_disallowed as $button) {
      $this->assertSession()->elementNotExists('xpath', "//*[@data-cme-button='" . $button . "']");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getWrapperSelector() {
    return '.js-form-item-body-0-value';
  }

  /**
   * Sets text format for body field.
   *
   * @param string $format
   *   The format.
   */
  protected function setBodyFormat($format) {
    $this->getSession()
      ->getPage()
      ->find('xpath', '//select[@name = "body[0][format]"]')
      ->selectOption($format);
    sleep(1);
  }

}
