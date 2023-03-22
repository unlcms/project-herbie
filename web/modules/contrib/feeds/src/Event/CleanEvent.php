<?php

namespace Drupal\feeds\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;

/**
 * Fired to begin cleaning.
 */
class CleanEvent extends EventBase {

  /**
   * The entity to clean.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a CleanEvent object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to clean.
   */
  public function __construct(FeedInterface $feed, EntityInterface $entity) {
    $this->feed = $feed;
    $this->entity = $entity;
  }

  /**
   * Returns the entity to clean.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to clean.
   */
  public function getEntity() {
    return $this->entity;
  }

}
