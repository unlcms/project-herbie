<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a number field mapper.
 *
 * @FeedsTarget(
 *   id = "number",
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "list_float"
 *   }
 * )
 */
class Number extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');

    // Only the decimal field type is supported as unique target. The float
    // field type isn't because it cannot be precisely selected with the 'equal'
    // operator.
    // @see https://stackoverflow.com/questions/1302243/selecting-a-float-in-mysql
    if ($field_definition->getType() === 'decimal') {
      $definition->markPropertyUnique('value');
    }

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = is_string($values['value']) ? trim($values['value']) : $values['value'];

    if (!is_numeric($values['value'])) {
      $values['value'] = '';
    }
  }

}
