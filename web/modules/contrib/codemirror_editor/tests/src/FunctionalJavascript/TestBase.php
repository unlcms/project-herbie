<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for CodeMirror editor tests.
 */
abstract class TestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['codemirror_editor', 'codemirror_editor_test'];

  /**
   * Active editor.
   *
   * @var int
   */
  protected $activeEditor;

  /**
   * Sets CodeMirror document value.
   */
  protected function editorSetValue($value) {
    $script = 'document.querySelector("%s .CodeMirror").CodeMirror.setValue("%s");';
    $script = sprintf($script, $this->getWrapperSelector(), $value);
    $this->getSession()
      ->getDriver()
      ->executeScript($script);
  }

  /**
   * Sets CodeMirror document selection.
   */
  protected function editorSetSelection($anchor, $head) {
    $script = 'document.querySelector("%s .CodeMirror").CodeMirror.setSelection({line: %d, ch: %d}, {line: %d, ch: %d});';
    $script = sprintf($script, $this->getWrapperSelector(), $anchor[0], $anchor[1], $head[0], $head[1]);
    $this->getSession()
      ->getDriver()
      ->executeScript($script);
  }

  /**
   * Clicks specified editor toolbar button.
   */
  protected function editorClickButton($button) {
    $this->getSession()
      ->getPage()
      ->find('css', sprintf('%s [data-cme-button="' . $button . '"]', $this->getWrapperSelector()))
      ->click();
  }

  /**
   * Changes editor mode.
   */
  protected function changeMode($mode) {
    $this->getSession()
      ->getPage()
      ->find('css', '.cme-mode')
      ->selectOption($mode);
  }

  /**
   * Clicks button or link located by it's XPath query.
   */
  protected function click($xpath) {
    $this->getSession()->getDriver()->click($xpath);
  }

  /**
   * Assets that toolbar exists.
   */
  protected function assertToolbarExists() {
    $this->assertSession()
      ->elementExists('css', $this->getWrapperSelector() . ' .cme-toolbar');
  }

  /**
   * Assets that toolbar does not exist.
   */
  protected function assertToolbarNotExists() {
    $this->assertSession()
      ->elementNotExists('css', $this->getWrapperSelector() . ' .cme-toolbar');
  }

  /**
   * Assets editor option value.
   */
  protected function assertEditorOption($option, $expected_value) {
    $script = 'document.querySelector("%s .CodeMirror").CodeMirror.getOption("%s");';
    $script = sprintf($script, $this->getWrapperSelector(), $option);
    $value = $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
    self::assertSame($expected_value, $value);
  }

  /**
   * Assets editor height.
   */
  protected function assertEditorHeight($expected_height) {
    $script = 'document.querySelector("%s .CodeMirror").style.height;';
    $script = sprintf($script, $this->getWrapperSelector());
    $height = $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
    self::assertEquals($expected_height, $height);
  }

  /**
   * Assets editor scroller min height.
   */
  protected function assertScrollerMinHeight($expected_height) {
    $script = 'document.querySelector("%s .CodeMirror .CodeMirror-scroll").style.minHeight;';
    $script = sprintf($script, $this->getWrapperSelector());
    $height = (int) $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
    self::assertLessThan(5, abs($expected_height - $height));
  }

  /**
   * Assets editor value.
   */
  protected function assertEditorValue($expected_value) {
    $script = 'document.querySelector("%s .CodeMirror").CodeMirror.getValue();';
    $script = sprintf($script, $this->getWrapperSelector());
    $value = $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
    self::assertSame($expected_value, $value);
  }

  /**
   * Assets that specific element exists on the current page.
   */
  protected function assertElementExist($xpath) {
    $this->assertSession()->elementExists('xpath', $xpath);
  }

  /**
   * Assets that specific element does not exist on the current page.
   */
  protected function assertElementNotExist($xpath) {
    $this->assertSession()->elementNotExists('xpath', $xpath);
  }

  /**
   * Asserts that the provided element is visible.
   */
  protected function assertVisible($xpath) {
    $is_visible = $this->getSession()
      ->getDriver()
      ->isVisible($xpath);
    $this->assertTrue($is_visible);
  }

  /**
   * Asserts that the provided element is not visible.
   */
  protected function assertNotVisible($xpath) {
    $is_visible = $this->getSession()
      ->getDriver()
      ->isVisible($xpath);
    $this->assertFalse($is_visible);
  }

  /**
   * Gets wrapper selector for CodeMirror.
   */
  protected function getWrapperSelector() {
    return '.js-form-item-editor-' . $this->activeEditor;
  }

  /**
   * Scrolls down the page.
   */
  protected function scrollToBottom() {
    $this->getSession()
      ->getDriver()
      ->evaluateScript('window.scrollTo(0, document.body.scrollHeight)');
  }

}
