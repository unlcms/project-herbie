<?php

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\State\CleanStateInterface;

/**
 * Interface for plugins that need to perform cleanup tasks after processing.
 */
interface CleanableInterface {

  /**
   * Applies an action to an entity to 'clean' it.
   *
   * An action is applied on an entity for which the source item no longer
   * exists in the feed.
   *
   * An action can be:
   * - Deleting the entity;
   * - Unpublishing the entity;
   * - Or any other action plugin that is applyable.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being cleaned.
   * @param \Drupal\feeds\Feeds\State\CleanStateInterface $state
   *   The state object.
   */
  public function clean(FeedInterface $feed, EntityInterface $entity, CleanStateInterface $state);

}
