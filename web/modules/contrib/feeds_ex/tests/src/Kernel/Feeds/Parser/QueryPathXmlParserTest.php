<?php

namespace Drupal\Tests\feeds_ex\Kernel\Feeds\Parser;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds_ex\Kernel\FeedsExKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser
 * @group feeds_ex
 */
class QueryPathXmlParserTest extends FeedsExKernelTestBase {

  /**
   * Tests that Blank sources are ignored by the XML parser.
   */
  public function testImportWithBlankSource() {
    $this->setUpBodyField();

    // Create a feed type using the XML parser.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'xml',
      ],
      'parser' => 'querypathxml',
      'parser_configuration' => [
        'context' => [
          'value' => 'root item',
        ],
      ],
      'custom_sources' => [
        'title' => [
          'label' => 'Title',
          'machine_name' => 'title',
          'value' => 'title',
          'attribute' => '',
          'raw' => FALSE,
          'inner' => FALSE,
          'type' => 'querypathxml',
        ],
        'body' => [
          'label' => 'Body',
          'value' => 'body',
          'machine_name' => 'body',
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
      ],
    ]);

    // Create a feed and import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/content.xml',
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
  }

}
