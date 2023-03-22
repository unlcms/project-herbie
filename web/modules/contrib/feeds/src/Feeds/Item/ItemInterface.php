<?php

namespace Drupal\feeds\Feeds\Item;

/**
 * The interface for a single feed item.
 */
interface ItemInterface {

  /**
   * Returns the value for a target field.
   *
   * @param string $field
   *   The name of the field.
   *
   * @return mixed|null
   *   The value that corresponds to this field, or null if it does not exist.
   */
  public function get($field);

  /**
   * Sets a value for a field.
   *
   * @param string $field
   *   The name of the field.
   * @param mixed $value
   *   The value for the field.
   *
   * @return $this
   *   An instance of this class.
   */
  public function set($field, $value);

  /**
   * Converts the item to an array.
   *
   * @return array
   *   The data of the item.
   */
  public function toArray();

  /**
   * Loads data in from an array.
   *
   * @param array $data
   *   The data to load in.
   *
   * @return $this
   *   An instance of this class.
   */
  public function fromArray(array $data);

}
