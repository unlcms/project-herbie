<?php

/**
 * @file
 * Post update functions for Feeds.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Parser\CsvParser;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Replace deprecated action ID's for 'update_non_existent' setting.
 */
function feeds_post_update_actions_update_non_existent(&$sandbox = NULL) {
  $action_id_map = [
    'comment_delete_action' => 'entity:delete_action:comment',
    'comment_publish_action' => 'entity:publish_action:comment',
    'comment_unpublish_action' => 'entity:unpublish_action:comment',
    'comment_save_action' => 'entity:save_action:comment',
    'node_delete_action' => 'entity:delete_action:node',
    'node_publish_action' => 'entity:publish_action:node',
    'node_unpublish_action' => 'entity:unpublish_action:node',
    'node_save_action' => 'entity:save_action:node',
  ];
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'feeds_feed_type', function (FeedTypeInterface $feed_type) use ($action_id_map) {
      $config = $feed_type->getProcessor()
        ->getConfiguration();
      if (isset($action_id_map[$config['update_non_existent']])) {
        $config['update_non_existent'] = $action_id_map[$config['update_non_existent']];
        $feed_type->getProcessor()
          ->setConfiguration($config);
        return TRUE;
      };
      return FALSE;
    });
}

/**
 * Add types to existing custom sources on feeds parsers.
 */
function feeds_post_update_custom_sources(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'feeds_feed_type', function (FeedTypeInterface $feed_type) {
      $parser = $feed_type->getParser();
      if ($parser instanceof CsvParser) {
        $custom_source_type = 'csv';
      }
      else {
        return FALSE;
      }

      // Add type to custom sources to those that don't have it yet.
      foreach ($feed_type->getCustomSources() as $name => $custom_source) {
        if (empty($custom_source['type'])) {
          $custom_source['type'] = $custom_source_type;
          $feed_type->addCustomSource($name, $custom_source);
        }
      }

      return TRUE;
    });
}

/**
 * The feeds_item field storage config is updated to unlimited cardinality.
 */
function feeds_post_update_ensure_feeds_item_storage_config_cardinality_is_unlimited(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'field_storage_config', function (FieldStorageConfigInterface $field_storage_config) {
      if ($field_storage_config->getType() !== 'feeds_item') {
        return FALSE;
      }

      $field_storage_config->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
      return TRUE;
    });
}
