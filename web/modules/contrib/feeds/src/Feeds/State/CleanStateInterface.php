<?php

namespace Drupal\feeds\Feeds\State;

use Countable;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\feeds\StateInterface;
use IteratorAggregate;

/**
 * Status of the clean phase of an import.
 */
interface CleanStateInterface extends StateInterface, IteratorAggregate, Countable {

  /**
   * Returns if the list is initiated.
   *
   * @return bool
   *   True if the list was initiated, false otherwise.
   */
  public function initiated();

  /**
   * Sets the list of entity ID's.
   *
   * @param array $ids
   *   An array of entity ID's.
   */
  public function setList(array $ids);

  /**
   * Returns the list of entity ID's.
   *
   * @return array
   *   An array of entity ID's.
   */
  public function getList();

  /**
   * Removes a specific item from the list.
   *
   * @param mixed $entity_id
   *   The ID of the entity to remove.
   */
  public function removeItem($entity_id);

  /**
   * Returns the next entity in the list and removes the ID from the list.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   (optional) The entity storage.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the next the entity in the list, if found.
   */
  public function nextEntity(EntityStorageInterface $storage = NULL);

  /**
   * Sets the entity type ID of the entity ID's on the list.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   */
  public function setEntityTypeId($entity_type_id);

  /**
   * Returns the entity type ID of the entity ID's on the list.
   *
   * @return string
   *   An entity type ID.
   */
  public function getEntityTypeId();

}
