<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;

/**
 * Tests mapping multiple times to the same target.
 *
 * @group feeds
 */
class MultiMappingTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['field', 'filter', 'node', 'datetime']);
  }

  /**
   * Tests importing two values to the same target.
   */
  public function testImportTwoValues() {
    // Create a text field that can hold an unlimited amount of values.
    $this->createFieldWithStorage('field_alpha', [
      'storage' => [
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ],
    ]);

    // Create a feed type, map two sources to the same target.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'alpha' => 'alpha',
      'beta' => 'beta',
    ], [
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'alpha'],
          'settings' => [
            'format' => 'plain_text',
          ],
        ],
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'beta'],
          'settings' => [
            'format' => 'plain_text',
          ],
        ],
      ],
    ]);

    // Import data.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Check the values imported into field_alpha.
    $nodes = Node::loadMultiple();
    $expected_values_per_node = [
      1 => [
        ['value' => 'Lorem', 'format' => 'plain_text'],
        ['value' => '42', 'format' => 'plain_text'],
      ],
      2 => [
        ['value' => 'Ut wisi', 'format' => 'plain_text'],
        ['value' => '32', 'format' => 'plain_text'],
      ],
    ];
    foreach ($expected_values_per_node as $node_id => $expected_values) {
      $this->assertEquals($expected_values, $nodes[$node_id]->field_alpha->getValue());
    }
  }

  /**
   * Tests importing two date values to the same target.
   *
   * The target configuration per date target differs to ensure that this
   * configuration is respected per value. The first date target is configured
   * to interpret date values in the "UTC" timezone, while the second should
   * interpret it as "Europe/Amsterdam".
   */
  public function testImportTwoDateValues() {
    // Create a text field that can hold an unlimited amount of values.
    $this->createFieldWithStorage('field_date', [
      'type' => 'datetime',
      'storage' => [
        'settings' => ['datetime_type' => 'datetime'],
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ],
    ]);

    // Create a feed type, map two sources to the same target.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'datetime_start' => 'datetime_start',
      'datetime_end' => 'datetime_end',
    ], [
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'field_date',
          'map' => ['value' => 'datetime_start'],
          'settings' => [
            'timezone' => 'UTC',
          ],
        ],
        [
          'target' => 'field_date',
          'map' => ['value' => 'datetime_end'],
          'settings' => [
            'timezone' => 'Europe/Amsterdam',
          ],
        ],
      ],
    ]);

    // Import data.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content_date.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(4);

    // Check the values imported into field_date.
    $nodes = Node::loadMultiple();
    $expected_values_per_node = [
      1 => [
        ['value' => '1955-11-05T12:00:00'],
        ['value' => '1955-11-05T14:00:00'],
      ],
      2 => [
        ['value' => '2015-10-21T23:29:00'],
        ['value' => '2015-10-22T00:29:00'],
      ],
      3 => [
        ['value' => '2018-02-09T00:00:00'],
        ['value' => '2018-02-10T22:00:00'],
      ],
    ];
    foreach ($expected_values_per_node as $node_id => $expected_values) {
      $this->assertEquals($expected_values, $nodes[$node_id]->field_date->getValue());
    }
  }

}
