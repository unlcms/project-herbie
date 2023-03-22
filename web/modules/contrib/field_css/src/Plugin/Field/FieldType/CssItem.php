<?php

namespace Drupal\field_css\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the CSS field type.
 *
 * @FieldType(
 *   id = "css",
 *   label = @Translation("CSS"),
 *   category = @Translation("General"),
 *   default_widget = "css",
 *   default_formatter = "css"
 * )
 */
class CssItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->value !== NULL) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('CSS'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'value' => [
        'type' => 'text',
        'size' => 'big',
      ],
    ];

    $schema = [
      'columns' => $columns,
    ];

    return $schema;
  }

}
