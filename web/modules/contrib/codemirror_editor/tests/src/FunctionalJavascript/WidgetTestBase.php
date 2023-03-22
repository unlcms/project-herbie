<?php

namespace Drupal\Tests\codemirror_editor\FunctionalJavascript;

/**
 * Base class for CodeMirror editor widget tests.
 */
abstract class WidgetTestBase extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
  ];

  /**
   * The machine name of the content type of field to be tested.
   *
   * @var string
   */
  public $contentTypeName;

  /**
   * The machine name of the field to be tested.
   *
   * @var string
   */
  public $fieldName;

}
