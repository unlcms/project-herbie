<?php

namespace Drupal\feeds\Feeds\Source;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Source\SourceBase;

/**
 * A source plugin that provides feed type fields as mapping sources.
 *
 * @FeedsSource(
 *   id = "basic_field",
 *   category = @Translation("Feed entity"),
 * )
 */
class BasicFieldSource extends SourceBase {

  /**
   * {@inheritdoc}
   */
  public static function sources(array &$sources, FeedTypeInterface $feed_type, array $definition) {
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('feeds_feed', $feed_type->id());
    foreach ($field_definitions as $field => $field_definition) {
      if (!$field_definition->getFieldStorageDefinition()->getMainPropertyName()) {
        // No main property known. Skip this field.
        continue;
      }
      $sources['parent:' . $field] = [
        'label' => t('Feed: @label', ['@label' => $field_definition->getLabel()]),
        'description' => $field_definition->getDescription(),
        'id' => $definition['id'],
        'type' => (string) $definition['category'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo I guess we could cache this since the value will be the same for
   *   source/feed id combo.
   */
  public function getSourceElement(FeedInterface $feed, ItemInterface $item) {
    list(, $field_name) = explode(':', $this->configuration['source']);
    $return = [];

    if ($field_list = $feed->get($field_name)) {
      foreach ($field_list as $field) {
        $main_property_name = $field->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
        if ($main_property_name) {
          $return[] = $field->{$main_property_name};
        }
      }
    }

    return $return;
  }

}
