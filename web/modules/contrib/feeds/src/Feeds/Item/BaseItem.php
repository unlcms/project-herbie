<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * Defines a base item class.
 */
abstract class BaseItem implements ItemInterface {

  /**
   * {@inheritdoc}
   */
  public function get($field) {
    return isset($this->$field) ? $this->$field : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field, $value) {
    $this->$field = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return get_object_vars($this);
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
