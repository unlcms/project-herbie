<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * Defines an item class for when a parser has a dynamic set of fields.
 *
 * This should be avoided unless the parser allows dynamic field.
 */
class DynamicItem implements ItemInterface {

  /**
   * The field data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function get($field) {
    return isset($this->data[$field]) ? $this->data[$field] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field, $value) {
    $this->data[$field] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(array $data) {
    foreach ($data as $field => $value) {
      $this->set($field, $value);
    }
    return $this;
  }

}
