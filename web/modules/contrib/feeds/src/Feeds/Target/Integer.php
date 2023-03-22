<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;

/**
 * Defines an integer field mapper.
 *
 * @FeedsTarget(
 *   id = "integer",
 *   field_types = {
 *     "integer",
 *     "list_integer"
 *   }
 * )
 */
class Integer extends Number {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->markPropertyUnique('value');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $value = is_string($values['value']) ? trim($values['value']) : $values['value'];
    $values['value'] = is_numeric($value) ? (int) $value : '';
  }

}
