<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;

/**
 * Defines a telephone field mapper.
 *
 * @FeedsTarget(
 *   id = "telephone",
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class Telephone extends StringTarget {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value');

    if ($field_definition->getType() === 'string') {
      $definition->markPropertyUnique('value');
    }
    return $definition;
  }

}
