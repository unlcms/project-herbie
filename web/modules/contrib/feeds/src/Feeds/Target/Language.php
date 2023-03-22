<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\feeds\FeedInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a language field mapper.
 *
 * @FeedsTarget(
 *   id = "langcode",
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class Language extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    if ($values = $this->prepareValues($values)) {
      $langcode = isset($values[0]['value']) ? $values[0]['value'] : NULL;
      if (!empty($langcode)) {
        $entity->set($field_name, $langcode);
      }
    }
  }

}
