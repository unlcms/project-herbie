<?php

namespace Drupal\Tests\feeds\Functional\Update;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Tests\feeds\Traits\FeedCreationTrait;
use Drupal\Tests\feeds\Traits\FeedsCommonTrait;

/**
 * Provides a test to check updating feeds_item cardinality.
 *
 * @group feeds
 * @group Update
 * @group legacy
 */
class UpdateFeedsItemFieldsCardinalityUpdateTest extends UpdatePathTestBase {

  use FeedCreationTrait;
  use FeedsCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->getCoreFixturePath(9),
      __DIR__ . '/../../../fixtures/feeds-8.x-3.0-beta1-feeds_installed.php',
    ];
  }

  /**
   * Tests updating existing feeds_item field cardinality to unlimited.
   */
  public function testUpdateFeedsItemFieldCardinality() {
    $entity_type_id = 'node';
    $bundle = 'article';
    $field_name = 'feeds_item';

    // Confirming that the feeds item field doesn't exist yet.
    $this->assertNull(FieldStorageConfig::loadByName($entity_type_id, $field_name));
    $this->assertNull(FieldConfig::loadByName($entity_type_id, $bundle, $field_name));

    // Creating the field manually with cardinality of 1 to mimic the previous
    // behavior.
    $this->createFieldWithStorage($field_name, [
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'type' => 'feeds_item',
      'label' => 'Feeds item',
      'storage' => [
        'translatable' => FALSE,
        'cardinality' => 1,
      ],
    ]);

    // Creating a feed to attach to nodes content.
    $feed = $this->createFeed('article_importer5', [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);

    // Adding 10 node items to be used during the updated when deleting and
    // restoring the data.
    $node_count = 10;
    for ($i = 1; $i <= $node_count; $i++) {
      $this->createNodeWithFeedsItem($feed);
    }
    $this->assertNodeCount($node_count);

    // Run the updates.
    $this->runUpdates();

    // Loading the field and check if the cardinality is now unlimited.
    $feeds_item_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    $this->assertEquals(FieldStorageConfigInterface::CARDINALITY_UNLIMITED, $feeds_item_storage->getCardinality());

    // Assert that the data for the feeds_item field still exists.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $count = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $bundle)
      ->condition($field_name, [$feed->id()], 'IN')
      ->count()
      ->execute();
    $this->assertEquals($node_count, $count);
  }

}
