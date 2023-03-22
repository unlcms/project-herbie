<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Tests the CodeMirror editor filter.
 *
 * @group codemirror_editor
 */
final class FilterTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $html = [
      '<code data-mode="html">',
      '<div class="field">Example</div>',
      '</code>',
      '<code data-mode="PHP">',
      'echo "Hello world!";',
      '</code>',
      '<code data-mode="text/css">',
      'body {',
      '  color: blue;',
      '}',
      '</code>',
    ];
    $this->createNode([
      'type' => 'test',
      'field_description' => [
        'value' => implode($html),
        'format' => 'codemirror',
      ],
    ]);
  }

  /**
   * Test callback.
   */
  public function testFilter() {

    $permissions = [
      'edit any test content',
      'administer filters',
      'use text format codemirror',
    ];
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    $this->drupalGet('node/1');
    $this->activeEditor = 1;
    $this->assertEditorOption('mode', 'text/html');
    $this->assertEditorOption('lineNumbers', TRUE);
    $this->assertEditorOption('foldGutter', FALSE);
    $this->activeEditor = 2;
    $this->assertEditorOption('mode', 'text/x-php');
    $this->assertEditorOption('lineNumbers', TRUE);
    $this->assertEditorOption('foldGutter', FALSE);
    $this->activeEditor = 3;
    $this->assertEditorOption('mode', 'text/css');
    $this->assertEditorOption('lineNumbers', TRUE);
    $this->assertEditorOption('foldGutter', FALSE);

    $this->drupalGet('admin/config/content/formats/manage/codemirror');
    $assert_session = $this->assertSession();
    $xpath = '//input[@name = "filters[codemirror_editor][settings][lineNumbers]" and @checked = "checked"]';
    $assert_session->elementExists('xpath', $xpath);
    $xpath = '//input[@name = "filters[codemirror_editor][settings][foldGutter]" and not(@checked)]';
    $assert_session->elementExists('xpath', $xpath);

    $this->scrollToBottom();
    $edit = [
      'filters[codemirror_editor][settings][lineNumbers]' => FALSE,
      'filters[codemirror_editor][settings][foldGutter]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('node/1');
    $this->activeEditor = 1;
    $this->assertEditorOption('mode', 'text/html');
    $this->assertEditorOption('lineNumbers', FALSE);
    $this->assertEditorOption('foldGutter', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getWrapperSelector() {
    return sprintf('.cme-wrapper:nth-child(%s)', $this->activeEditor);
  }

}
