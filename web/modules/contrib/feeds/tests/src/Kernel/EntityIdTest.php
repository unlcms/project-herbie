<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;

/**
 * Tests for inserting and updating entity ID's.
 *
 * @group feeds
 */
class EntityIdTest extends FeedsKernelTestBase {

  /**
   * Tests creating a node where the source dictates the node ID.
   */
  public function testInsertNodeId() {
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'beta' => 'beta',
    ], [
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'nid',
          'map' => ['value' => 'beta'],
        ],
      ],
    ]);

    // Import data.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Check the imported values.
    $node = Node::load(42);
    $this->assertEquals('Lorem ipsum', $node->title->value);
    $node = Node::load(32);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node->title->value);

    // Ensure that an other import doesn't result into SQL errors.
    $feed->import();

    // Ensure that there are no SQL warnings.
    $messages = \Drupal::messenger()->all();
    foreach ($messages['warning'] as $warning) {
      $this->assertStringNotContainsString('SQLSTATE', $warning);
    }
  }

  /**
   * Tests updating an existing node using node ID.
   */
  public function testUpdateByNodeId() {
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'beta' => 'beta',
    ], [
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'nid',
          'map' => ['value' => 'beta'],
          'unique' => ['value' => TRUE],
        ],
      ],
    ]);

    // Create a node with ID 42.
    $node = Node::create([
      'nid' => 42,
      'title' => 'Foo',
      'type' => 'article',
    ]);
    $node->save();

    // Import data.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Ensure that the title got updated.
    $node = $this->reloadEntity($node);
    $this->assertEquals('Lorem ipsum', $node->title->value);
  }

  /**
   * Tests that node ID's don't change and that existing nodes are not hijacked.
   */
  public function testNoNodeIdChange() {
    // Create two existing articles.
    $node1 = Node::create([
      'title' => 'Lorem ipsum',
      'type' => 'article',
    ]);
    $node1->save();
    // Create an existing article with ID 32.
    $node32 = Node::create([
      'nid' => 32,
      'title' => 'Foo',
      'type' => 'article',
    ]);
    $node32->save();

    // Create a feed type, update by title.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'beta' => 'beta',
    ], [
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'nid',
          'map' => ['value' => 'beta'],
        ],
      ],
    ]);

    // Import data.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);

    // Ensure that node 42 doesn't exist.
    $this->assertNull(Node::load(42));

    // Ensure that node 32 is still called 'Foo'.
    $node32 = $this->reloadEntity($node32);
    $this->assertEquals('Foo', $node32->title->value);

    // Ensure that there are no SQL warnings.
    $messages = \Drupal::messenger()->all();
    foreach ($messages['warning'] as $warning) {
      $this->assertStringNotContainsString('SQLSTATE', $warning);
    }
  }

}
