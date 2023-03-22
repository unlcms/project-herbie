<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Target\TargetBase;
use Drupal\feeds\TargetDefinition;

/**
 * Defines a target that does not set data.
 *
 * @FeedsTarget(
 *   id = "temporary_target"
 * )
 */
class Temporary extends TargetBase {

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition) {
    $targets['temporary_target'] = TargetDefinition::create()
      ->setPluginId($definition['id'])
      ->setLabel(t('Temporary target'))
      ->addProperty('temporary', t('Temporary'));
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $target, array $values) {
    // Do nothing because this is only a placeholder target.
  }

  /**
   * {@inheritdoc}
   */
  public function isMutable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(FeedInterface $feed, EntityInterface $entity, $target) {
    return TRUE;
  }

}
