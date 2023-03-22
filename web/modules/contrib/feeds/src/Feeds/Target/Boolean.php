<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a boolean field mapper.
 *
 * @FeedsTarget(
 *   id = "boolean",
 *   field_types = {
 *     "boolean",
 *     "list_boolean"
 *   }
 * )
 */
class Boolean extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = $this->convertValue($values['value']);
  }

  /**
   * Converts the given value to a boolean.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return bool
   *   The value, converted to a boolean.
   */
  protected function convertValue($value): bool {
    if (is_bool($value)) {
      return $value;
    }
    if (is_string($value)) {
      return (bool) trim($value);
    }
    if (is_scalar($value)) {
      return (bool) $value;
    }
    if (empty($value)) {
      return FALSE;
    }
    if (is_array($value)) {
      $value = current($value);
      return $this->convertValue($value);
    }

    $value = @(string) $value;
    return $this->convertValue($value);
  }

}
