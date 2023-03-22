<?php

namespace Drupal\feeds\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Fired at various phases during the process stage.
 */
class EntityEvent extends EventBase {

  /**
   * The entity being inserted or updated.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The item that is being processed.
   *
   * @var \Drupal\feeds\Feeds\Item\ItemInterface
   */
  protected $item;

  /**
   * Constructs an EntityEvent object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being inserted or updated.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item that is being processed.
   */
  public function __construct(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    parent::__construct($feed);
    $this->entity = $entity;
    $this->item = $item;
  }

  /**
   * Returns the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being inserted or updated.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns the item.
   *
   * @return \Drupal\feeds\Feeds\Item\ItemInterface
   *   The item that is being processed.
   */
  public function getItem() {
    return $this->item;
  }

}
