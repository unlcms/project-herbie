<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\node\Entity\Node;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests for the config entity reference target.
 *
 * @group feeds
 */
class ConfigEntityReferenceTest extends FeedsKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create two config entities.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Lorem',
      'description' => 'My test description',
    ])->save();

    EntityTestBundle::create([
      'id' => 'test2',
      'label' => 'Ut wisi',
      'description' => 'My test2 description',
    ])->save();

    // Create a config entity reference field.
    $this->createEntityReferenceField('node', 'article', 'field_entity_test_type', 'Type', 'entity_test_bundle');
  }

  /**
   * Tests importing config entity references by ID.
   */
  public function testImportById() {
    // Create a feed type, map to created field.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'type' => 'type',
    ], [
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_entity_test_type',
          'map' => ['target_id' => 'type'],
          'settings' => ['reference_by' => 'id'],
        ],
      ]),
    ]);

    // Import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content-with-config-reference.csv',
    ]);
    $feed->import();

    // Assert two created nodes.
    $this->assertNodeCount(2);

    // Test target id values of these nodes.
    $expected_values = [
      1 => 'test',
      2 => 'test2',
    ];
    foreach ($expected_values as $nid => $expected_value) {
      $node = Node::load($nid);
      $this->assertEquals($expected_value, $node->field_entity_test_type->target_id);
    }
  }

  /**
   * Tests importing config entity references by label.
   */
  public function testImportByLabel() {
    // Create a feed type, map to created field.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'alpha' => 'alpha',
    ], [
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_entity_test_type',
          'map' => ['target_id' => 'alpha'],
          'settings' => ['reference_by' => 'label'],
        ],
      ]),
    ]);

    // Import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert two created nodes.
    $this->assertNodeCount(2);

    // Test target id values of these nodes.
    $expected_values = [
      1 => 'test',
      2 => 'test2',
    ];
    foreach ($expected_values as $nid => $expected_value) {
      $node = Node::load($nid);
      $this->assertEquals($expected_value, $node->field_entity_test_type->target_id);
    }
  }

  /**
   * Tests importing config entity references by UUID.
   */
  public function testImportByUuid() {
    // Because it's unpredictable which uuids a config entity gets, let's add an
    // event subscriber that sets these values for the 'type' column.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::PARSE, function (ParseEvent $event) {
        // Set UUID on items.
        $counter = 0;
        $config_entities = ['test', 'test2'];
        foreach ($event->getParserResult() as $item) {
          $uuid = $this->entityTypeManager->getStorage('entity_test_bundle')
            ->load($config_entities[$counter])
            ->uuid();
          $item->set('type', $uuid);
          $counter++;
        }
      }, FeedsEvents::AFTER);

    // Create a feed type, map to created field.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'type' => 'type',
    ], [
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_entity_test_type',
          'map' => ['target_id' => 'type'],
          'settings' => ['reference_by' => 'uuid'],
        ],
      ]),
    ]);

    // Import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert two created nodes.
    $this->assertNodeCount(2);

    // Test target id values of these nodes.
    $expected_values = [
      1 => 'test',
      2 => 'test2',
    ];
    foreach ($expected_values as $nid => $expected_value) {
      $node = Node::load($nid);
      $this->assertEquals($expected_value, $node->field_entity_test_type->target_id);
    }
  }

}
