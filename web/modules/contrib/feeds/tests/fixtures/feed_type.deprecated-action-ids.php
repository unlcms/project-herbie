<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/project/feeds/issues/3094433.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Structure of feed types, some making use of a deprecated action plugin.
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.testfor800201.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.testfor800202.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.testfor800203.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.testfor800204.yml'));

foreach ($feed_type_configs as $feed_type_config) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'feeds.feed_type.' . $feed_type_config['id'],
      'data' => serialize($feed_type_config),
    ])
    ->execute();
}
