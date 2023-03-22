<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Tests the CodeMirror field widget (string_long).
 *
 * @group codemirror_editor
 */
final class WidgetStringTest extends WidgetTestBase {

  // Tests in trait are automatically executed.
  use WidgetTestTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // The 'string_long' widget uses the 'test' test content type.
    $this->contentTypeName = 'test';
    $this->fieldName = 'field_code';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWrapperSelector() {
    return '.js-form-item-field-code-0-value';
  }

}
