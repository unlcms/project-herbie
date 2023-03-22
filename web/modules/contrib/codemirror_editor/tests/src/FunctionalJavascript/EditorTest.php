<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Tests the CodeMirror editor.
 *
 * @group codemirror_editor
 */
final class EditorTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';

  /**
   * Test callback.
   */
  public function testEditor() {

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('codemirror-editor-test');

    $this->activeEditor = 1;

    // -- Test default options.
    $this->assertEditorOption('lineWrapping', FALSE);
    $this->assertEditorOption('lineNumbers', FALSE);
    $this->assertEditorOption('mode', 'text/html');
    $this->assertEditorOption('readOnly', FALSE);
    $this->assertEditorOption('theme', 'default');
    $this->assertEditorOption('foldGutter', FALSE);
    $this->assertEditorOption('autoCloseTags', TRUE);
    $this->assertEditorOption('styleActiveLine', FALSE);
    $this->assertEditorOption('autoRefresh', ['delay' => 3000]);
    $this->assertEditorHeight(NULL);
    $this->assertScrollerMinHeight(225);
    $toolbar_xpath = '//div[contains(@class, "js-form-item-editor-1")]//div[@class = "cme-toolbar"]';
    $this->assertElementExist($toolbar_xpath);
    self::assertCount(13, $this->xpath($toolbar_xpath . '/*[@class = "cme-button"]'));

    // -- Test HTML editor buttons.
    $this->editorClickButton('bold');
    $this->editorClickButton('italic');
    $this->editorClickButton('underline');
    $this->editorClickButton('strike-through');
    $this->editorClickButton('list-numbered');
    $this->editorClickButton('list-bullet');
    $this->editorClickButton('link');
    $this->editorClickButton('horizontal-rule');
    $expected = [
      '<strong></strong><em></em><u></u><s></s><ol>',
      '  <li></li>',
      '</ol>',
      '<ul>',
      '  <li></li>',
      '</ul>',
      '<a href=""></a>',
      '<hr/>',
      '',
    ];
    $this->assertEditorValue(implode("\n", $expected));

    // -- Make sure the editors do not interfere with each other.
    $this->activeEditor = 2;
    $this->assertEditorValue('');

    // -- Test if it works with respect to selection.
    $this->activeEditor = 1;
    $this->editorSetValue('Test');
    $this->editorSetSelection([0, 0], [0, 4]);
    $this->editorClickButton('bold');
    $this->assertEditorValue('<strong>Test</strong>');

    // -- Test 'undo' button.
    $this->editorClickButton('undo');
    $this->assertEditorValue('Test');

    // -- Test 'redo' button.
    $this->editorClickButton('redo');
    $this->assertEditorValue('<strong>Test</strong>');

    // -- Test 'clear formatting' button.
    $this->editorSetSelection([0, 0], [0, 21]);
    $this->editorClickButton('clear-formatting');
    $this->assertEditorValue('Test');

    // -- Test 'enlarge' button.
    $this->editorClickButton('enlarge');
    $this->assertEditorOption('fullScreen', TRUE);

    $this->assertNotVisible('//*[@data-cme-button = "enlarge"]');
    $this->assertVisible('//*[@data-cme-button = "shrink"]');

    // -- Test 'shrink' button.
    $this->editorClickButton('shrink');
    $this->assertEditorOption('fullScreen', FALSE);
    $this->assertVisible('//*[@data-cme-button = "enlarge"]');
    $this->assertNotVisible('//*[@data-cme-button = "shrink"]');

    // -- Test 'mode' dropdown.
    $this->activeEditor = 2;
    $this->assertEditorOption('mode', 'text/html');
    $this->changeMode('javascript');
    $this->assertEditorOption('mode', 'javascript');
    $this->changeMode('css');
    $this->assertEditorOption('mode', 'css');

    // Reload the page and make sure the mode has been preserved.
    $this->drupalGet('codemirror-editor-test');
    $this->assertSession()->elementExists('xpath', '//select[@class = "cme-mode"]/option');
    $selected_mode = $this->getSession()->getDriver()->evaluateScript('return jQuery(".cme-mode").val()');
    self::assertEquals('css', $selected_mode);
    $this->assertEditorOption('mode', 'css');

    // -- Test 'buttons' option.
    $toolbar_xpath = '//div[contains(@class, "js-form-item-editor-2")]//div[@class = "cme-toolbar"]';
    $this->assertElementExist($toolbar_xpath . '/*[@data-cme-button = "bold"]');
    $this->assertElementExist($toolbar_xpath . '/*[@data-cme-button = "italic"]');
    $this->assertElementExist($toolbar_xpath . '/*[@data-cme-button = "underline"]');
    self::assertCount(3, $this->xpath($toolbar_xpath . '/*[@class = "cme-button"]'));

    // -- Test 'autoCloseTags' option.
    $this->assertEditorOption('autoCloseTags', FALSE);

    // -- Test 'styleActiveLine' option.
    $this->assertEditorOption('styleActiveLine', TRUE);

    // -- Test 'mode' option.
    $this->activeEditor = 3;
    $this->assertEditorOption('mode', 'text/html');

    // -- Test 'modeSelect' option.
    $this->assertElementExist($toolbar_xpath . '/select[@class = "cme-mode"]');

    // -- Test 'lineWrapping' option.
    $this->assertVisible('//div[contains(@class, "js-form-item-editor-2")]//div[@class = "CodeMirror-gutters"]');

    // -- Test 'lineNumbers' option.
    $this->assertVisible('//div[contains(@class, "js-form-item-editor-3")]//div[@class = "CodeMirror-gutters"]');

    // -- Test 'toolbar' option.
    $this->activeEditor = 3;
    $this->assertElementNotExist($toolbar_xpath . '//div[contains(@class, "js-form-item-editor-3")]//div[@class = "cme-toolbar"]');

    // -- Test 'height' option.
    $this->assertEditorHeight('100px');

    // -- Test 'readOnly' option.
    $this->assertEditorOption('readOnly', TRUE);

    // -- Test 'foldGutter' option.
    $this->assertEditorOption('foldGutter', TRUE);
  }

}
