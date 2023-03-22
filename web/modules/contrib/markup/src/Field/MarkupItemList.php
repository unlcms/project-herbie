<?php

namespace Drupal\markup\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * Defines a item list class for markup fields.
 *
 * @internal
 *   Plugin classes are internal.
 *
 * @see \Drupal\markup\Plugin\Field\FieldType\MarkupItem
 */
class MarkupItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->getFieldDefinition()->getSetting('markup')['value'];
    return $value === NULL || $value === '';
  }

}
