<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Tests the CodeMirror field widget (text_long).
 *
 * @group codemirror_editor
 */
final class WidgetFormattedTest extends WidgetTestBase {

  // Tests in trait are automatically executed.
  use WidgetTestTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // The 'string_long' widget uses the 'test2' test content type.
    $this->contentTypeName = 'test2';
    $this->fieldName = 'field_code2';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWrapperSelector() {
    return '.js-form-item-field-code2-0-value';
  }

}
