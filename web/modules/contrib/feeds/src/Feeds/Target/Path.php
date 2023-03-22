<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a path field mapper.
 *
 * @FeedsTarget(
 *   id = "path",
 *   field_types = {"path"}
 * )
 */
class Path extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $target_definition = FieldTargetDefinition::createFromFieldDefinition($field_definition);
    $target_definition->addProperty('alias');

    if ($field_definition->getFieldStorageDefinition()->getPropertyDefinition('pathauto')) {
      $target_definition->addProperty('pathauto');
    }

    return $target_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if (array_key_exists('pathauto', $values)) {
      $values['pathauto'] = (int) (bool) $values['pathauto'];
    }
    else {
      $values['pathauto'] = 0;
    }

    if (isset($values['alias']) && is_string($values['alias'])) {
      $values['alias'] = trim($values['alias']);

      // Check if the alias is conform the regex.
      if (strlen($values['alias']) && !preg_match('/^\//i', $values['alias'])) {
        // Correct the alias by adding a slash.
        $values['alias'] = '/' . $values['alias'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isMutable() {
    // The path field is set to "computed", which evaluates to "read-only".
    // Ignore the fact that paths are read-only and mark it as mutable.
    return TRUE;
  }

}
