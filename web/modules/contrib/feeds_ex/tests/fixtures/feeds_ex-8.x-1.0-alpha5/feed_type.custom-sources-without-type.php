<?php
// @codingStandardsIgnoreFile
/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/project/feeds_ex/issues/3209655.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Structure of feed types, some making use of a deprecated action plugin.
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.xml.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.html.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.jmespath.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.jmespathlines.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.jsonpath.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.jsonpathlines.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.querypathxml.yml'));
$feed_type_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/feeds.feed_type.querypathhtml.yml'));

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
