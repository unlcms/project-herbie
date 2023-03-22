<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;

/**
 * Tests related to generating feeds item hashes.
 *
 * @group feeds
 */
class HashTest extends FeedsKernelTestBase {

  /**
   * Tests if items are not updated when only non-mapped data changes.
   */
  public function testIrrelevantUpdate() {
    // Create a feed type, map to guid and title only.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
      ],
    ]);

    // Create a feed and import first file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert two created nodes.
    $this->assertNodeCount(2);
    // Assert the expected titles of these nodes.
    $nodes = Node::loadMultiple();
    $expected_titles = [
      1 => 'Lorem ipsum',
      2 => 'Ut wisi enim ad minim veniam',
    ];
    foreach ($expected_titles as $node_id => $expected_title) {
      $this->assertEquals($expected_title, $nodes[$node_id]->title->value);
    }

    // Now manually change the titles of these nodes.
    for ($i = 1; $i <= 2; $i++) {
      $nodes[$i]->title->value = 'Node ' . $i;
      $nodes[$i]->save();
    }

    // Import feed on which only non-mapped columns changed. Only values in the
    // column 'body' are different and that column is *not* mapped.
    $feed->setSource($this->resourcesPath() . '/csv/content_updated.csv');
    $feed->save();
    $feed->import();

    // Ensure that no nodes were updated.
    for ($i = 1; $i <= 2; $i++) {
      $node = $this->reloadEntity($nodes[$i]);
      $this->assertEquals('Node ' . $i, $node->title->value);
    }
  }

}
