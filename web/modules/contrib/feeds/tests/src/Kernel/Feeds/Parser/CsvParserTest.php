<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Parser;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\CsvParser
 * @group feeds
 */
class CsvParserTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'feeds_test_alter_source',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpBodyField();
  }

  /**
   * Tests importing a CSV file with non machine name column names.
   *
   * This test ensures that CSV files can be imported where the CSV source's
   * machine names differ from CSV source's column names.
   */
  public function testImportCsvWithNonMachineNameColumnNames() {
    // Create a few text fields.
    $this->createFieldWithStorage('field_facade');
    $this->createFieldWithStorage('field_apres_ski');
    $this->createFieldWithStorage('field_a_la_carte');

    $feed_type = $this->createFeedTypeForCsv([
      'unique_identifier' => 'Unique identifier',
      'service_description' => 'Service Description',
      'facade' => 'Façade',
      'apres_ski' => 'Après-ski',
      'a_la_carte' => 'à la carte',
    ], [
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'unique_identifier'],
          'unique' => ['guid' => TRUE],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'service_description'],
        ],
        [
          'target' => 'field_facade',
          'map' => ['value' => 'facade'],
          'settings' => ['format' => 'plain_text'],
        ],
        [
          'target' => 'field_apres_ski',
          'map' => ['value' => 'apres_ski'],
          'settings' => ['format' => 'plain_text'],
        ],
        [
          'target' => 'field_a_la_carte',
          'map' => ['value' => 'a_la_carte'],
          'settings' => ['format' => 'plain_text'],
        ],
      ],
    ]);

    // Create a feed and import file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/with-non-machine-name-column-names.csv',
    ]);
    $feed->import();

    // Assert that 1 node has been created.
    static::assertEquals(1, $feed->getItemCount());
    $this->assertNodeCount(1);

    // Check the values on the node.
    $node = Node::load(1);
    $this->assertEquals(1, $node->get('feeds_item')->getItemByFeed($feed)->guid);
    $this->assertEquals('Window washer, Chimney sweeper', $node->title->value);
    $this->assertEquals('outside', $node->field_facade->value);
    $this->assertEquals('Having a meal', $node->field_apres_ski->value);
    $this->assertEquals('Salmon, spiced with dill', $node->field_a_la_carte->value);

    // Import again. Nothing should be imported.
    $feed->import();
    $this->assertNodeCount(1);
  }

  /**
   * Tests if data from a CSV file can be altered with an event subscriber.
   */
  public function testAlterCsvSource() {
    // Create a text field.
    $this->createFieldWithStorage('field_a_la_carte');

    $feed_type = $this->createFeedTypeForCsv([
      'unique_identifier' => 'Unique identifier',
      'service_description' => 'Service Description',
      'facade' => 'Façade',
      'apres_ski' => 'Après-ski',
      'a_la_carte' => 'à la carte',
    ], [
      // The module 'feeds_test_alter_source' alters the data for the feed type
      // 'csv'. In there, the title is converted to lower case and only the
      // first word is taken for 'à la carte'.
      'id' => 'csv',
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'unique_identifier'],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'service_description'],
        ],
        [
          'target' => 'field_a_la_carte',
          'map' => ['value' => 'a_la_carte'],
          'settings' => ['format' => 'plain_text'],
        ],
      ],
    ]);

    // Create a feed and import file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/with-non-machine-name-column-names.csv',
    ]);
    $feed->import();

    // Assert that 1 node has been created.
    static::assertEquals(1, $feed->getItemCount());
    $this->assertNodeCount(1);

    // Check the values on the node.
    $node = Node::load(1);
    $this->assertEquals(1, $node->get('feeds_item')->getItemByFeed($feed)->guid);
    $this->assertSame('window washer, chimney sweeper', $node->title->value);
    $this->assertSame('Salmon', $node->field_a_la_carte->value);
  }

  /**
   * Tests that Blank sources are ignored by the CSV parser.
   */
  public function testImportWithBlankSource() {
    $this->setUpBodyField();
    $this->createFieldWithStorage('field_alpha');

    // Create a feed type using the XML parser.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'parser_configuration' => [],
      'custom_sources' => [
        'title' => [
          'label' => 'Title',
          'value' => 'title',
          'machine_name' => 'title',
          'type' => 'csv',
        ],
        'body' => [
          'label' => 'Body',
          'value' => 'body',
          'machine_name' => 'body',
          'type' => 'blank',
        ],
        'alpha' => [
          'label' => 'Alpha',
          'value' => 'alpha',
          'machine_name' => 'alpha',
          'type' => 'blank',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
          'settings' => [
            'language' => NULL,
          ],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'body'],
          'settings' => [
            'format' => 'plain_text',
            'language' => NULL,
          ],
        ],
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'alpha'],
          'settings' => ['format' => 'plain_text'],
        ],
      ],
    ]);

    // Create a feed and import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that two nodes are created.
    $this->assertEquals(2, $feed->getItemCount());
    $this->assertNodeCount(2);

    // Assert that node has a title, but not a body because a custom source was
    // mapped to that.
    $node = Node::load(1);
    $this->assertEquals('Lorem ipsum', $node->title->value);
    $this->assertTrue($node->body->isEmpty());
    $this->assertTrue($node->field_alpha->isEmpty());
  }

}
