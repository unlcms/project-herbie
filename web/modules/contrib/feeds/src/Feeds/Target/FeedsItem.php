<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a feeds_item field mapper.
 *
 * @FeedsTarget(
 *   id = "feeds_item",
 *   field_types = {"feeds_item"}
 * )
 */
class FeedsItem extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('url')
      ->addProperty('guid')
      ->markPropertyUnique('url')
      ->markPropertyUnique('guid');
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values) {
    if ($values = $this->prepareValues($values)) {
      $entity_target = $this->getEntityTarget($feed, $entity);
      $item_list = $entity_target->get($field_name);

      // Append these values to the existing values.
      $values = array_merge($item_list->getValue()[0], $values[0]);

      $item_list->setValue($values);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if (isset($values['url']) && empty($values['url'])) {
      // If 'url' is empty, set it explicitly to NULL to prevent validation
      // errors.
      $values['url'] = NULL;
    }
  }

}
