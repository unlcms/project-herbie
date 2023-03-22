<?php

namespace Drupal\feeds\Plugin\Type\Processor;

use Drupal\Core\Entity\TranslatableInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\CleanableInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;

/**
 * Interface for Feeds entity processor plugins.
 */
interface EntityProcessorInterface extends ProcessorInterface, ClearableInterface, CleanableInterface, LockableInterface {

  /**
   * Returns a translation of the given entity.
   *
   * If a translation of the requested language does not exist yet on the
   * entity, one is created.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed that controls the import.
   * @param \Drupal\Core\Entity\TranslatableInterface $entity
   *   A translatable entity.
   * @param string $langcode
   *   The language in which to get the translation.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The translated entity.
   */
  public function getEntityTranslation(FeedInterface $feed, TranslatableInterface $entity, $langcode);

  /**
   * Returns the current language for entities.
   *
   * @return string
   *   The current language code.
   */
  public function entityLanguage();

}
