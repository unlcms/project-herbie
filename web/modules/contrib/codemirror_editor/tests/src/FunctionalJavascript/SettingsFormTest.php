<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Tests the CodeMirror editor settings form.
 *
 * @group codemirror_editor
 */
final class SettingsFormTest extends TestBase {

  /**
   * Test callback.
   */
  public function testSettingsForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make sure the form is not accessible by unprivileged users.
    $default_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($default_user);
    $this->drupalGet('admin/config/content/codemirror');
    $assert_session->pageTextContains('Access denied');

    $admin_user = $this->drupalCreateUser(['access content', 'administer codemirror editor']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/content/codemirror');
    $assert_session->pageTextContains('CodeMirror configuration');
    $assert_session->checkboxChecked('Use minified version of the library');
    $assert_session->checkboxChecked('Load the library from CDN');
    $assert_session->elementExists('xpath', '//select[@name = "theme"]/option[@value = "default" and @selected]');

    $all_checkboxes = $page->findAll('xpath', '//table[@id = "edit-language-modes"]//input[@type = "checkbox"]');
    self::assertCount(13, $all_checkboxes);

    /** @var \Behat\Mink\Element\NodeElement[] $checked_checkboxes */
    $checked_checkboxes = $page->findAll('xpath', '//table[@id = "edit-language-modes"]//input[@type = "checkbox" and @checked]');
    // Only XML is enabled by default.
    self::assertCount(1, $checked_checkboxes);
    self::assertEquals('language_modes[xml]', $checked_checkboxes[0]->getAttribute('name'));

    // Check PHP row.
    $php_row_xpath = '//table[@id = "edit-language-modes"]//tr[6]';
    $php_row_xpath .= '/td[//input[@type = "checkbox" and @name = "language_modes[php]" and not(@checked)]]';
    $php_row_xpath .= '/following-sibling::td[a[text() = "PHP" and @href = "https://codemirror.net/mode/php/index.html"]]';
    $php_row_xpath .= '/following-sibling::td[text() = "text/x-php, application/x-httpd-php"]';
    $php_row_xpath .= '/following-sibling::td[text() = "C-like"]/following-sibling::td[text() = "codemirror_editor_test"]';
    self::assertNotNull($page->find('xpath', $php_row_xpath));

    $page->selectFieldOption('Theme', 'cobalt');
    $page->checkField('language_modes[css]');
    $page->checkField('language_modes[javascript]');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('codemirror-editor-test');
    $this->activeEditor = 1;
    $this->assertEditorOption('theme', 'cobalt');

    // Check loaded modes.
    $result = $this->getSession()
      ->getDriver()
      ->evaluateScript('CodeMirror.modes');
    $expected_modes = [
      'clike',
      'css',
      'html_twig',
      'javascript',
      'null',
      'php',
      'xml',
    ];
    self::assertSame($expected_modes, array_keys($result));
  }

}
