<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Processor;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * Tests import various entity types.
 *
 * @group feeds
 */
class GenericContentEntityProcessorTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'feeds',
    'field',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'filter', 'node']);
  }

  /**
   * Tests importing various entity types.
   *
   * @dataProvider dataProviderEntityImport
   */
  public function testEntityImport($entity_type, array $mapping = [], array $feed_type_config = [], $settings = []) {
    if (empty($mapping)) {
      $mapping = [
        'name' => 'title',
      ];
    }

    $this->installEntitySchema('entity_test_bundle');
    $this->installEntitySchema($entity_type);

    // Create entity type.
    EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();

    // Create text field.
    if (isset($mapping['field_test_text'])) {
      $this->createFieldWithStorage('field_test_text', [
        'entity_type' => $entity_type,
        'bundle' => 'test',
      ]);
    }

    $custom_sources = [];
    $mappings = [];
    foreach ($mapping as $target => $source) {
      $custom_sources[$source] = [
        'label' => $source,
        'value' => $source,
        'machine_name' => $source,
      ];
      $mappings[] = [
        'target' => $target,
        'map' => ['value' => $source],
        'settings' => isset($settings[$source]) ? $settings[$source] : [],
      ];
    }

    $feed_type_config += [
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor' => 'entity:' . $entity_type,
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => $entity_type,
        ],
      ],
      'custom_sources' => $custom_sources,
      'mappings' => $mappings,
    ];

    // Create feed type.
    $feed_type = $this->createFeedType($feed_type_config);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Ensure no warnings nor errors were generated.
    $messages = \Drupal::messenger()->all();
    $this->assertArrayNotHasKey('warning', $messages);
    $this->assertArrayNotHasKey('error', $messages);

    // Test expected values.
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);

    $expected = [
      1 => [
        'title' => 'Lorem ipsum',
        'alpha' => 'Lorem',
      ],
      2 => [
        'title' => 'Ut wisi enim ad minim veniam',
        'alpha' => 'Ut wisi',
      ],
    ];
    foreach ($expected as $entity_id => $expected_values) {
      $entity = $storage->load($entity_id);
      foreach ($mapping as $target_name => $source_name) {
        $this->assertEquals($expected[$entity_id][$source_name], $entity->{$target_name}->value);
      }
    }
  }

  /**
   * Data provider for testEntityImport().
   */
  public function dataProviderEntityImport() {
    return [
      'entity_test' => [
        'entity_type' => 'entity_test',
      ],
      'entity_test_no_bundle' => [
        'entity_type' => 'entity_test_no_bundle',
        'mapping' => [],
        'feed_type_config' => [
          'processor_configuration' => [
            'authorize' => FALSE,
          ],
        ],
      ],
      'entity_test_no_label' => [
        'entity_type' => 'entity_test_no_label',
      ],
      'entity_test_no_uuid' => [
        'entity_type' => 'entity_test_no_uuid',
      ],
      'entity_test_rev' => [
        'entity_type' => 'entity_test_rev',
      ],
      'entity_test_with_bundle' => [
        'entity_type' => 'entity_test_with_bundle',
        'mapping' => [
          'name' => 'title',
          'field_test_text' => 'alpha',
        ],
        'feed_type_config' => [
          'processor_configuration' => [
            'authorize' => FALSE,
            'values' => [
              'type' => 'test',
            ],
          ],
        ],
        'settings' => [
          'field_test_text' => ['format' => 'plain_text'],
        ],
      ],
    ];
  }

}
