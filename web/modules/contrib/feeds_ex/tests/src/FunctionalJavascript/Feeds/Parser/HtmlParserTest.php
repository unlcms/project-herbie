<?php

namespace Drupal\Tests\feeds_ex\FunctionalJavascript\Feeds\Parser;

use Drupal\feeds\Entity\Feed;
use Drupal\node\Entity\Node;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\HtmlParser
 * @group feeds_ex
 */
class HtmlParserTest extends ParserTestBase {

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'html';

  /**
   * Tests adding a custom mapping source.
   */
  public function testMapCustomSource() {
    // Set context value.
    $edit = [
      'context' => '//div[@class="post"]',
    ];

    // Add mappings to feed item, title, body, alpha. Pass context value.
    $this->addMappings($this->feedType->id(), [
      [
        'target' => 'title',
        'map' => [
          'value' => [
            'value' => 'h3',
            'label' => 'Heading 3',
            'machine_name' => 'title_',
          ],
        ],
      ],
      [
        'target' => 'body',
        'map' => [
          'value' => [
            'value' => 'p',
            'label' => 'Paragraph',
            'machine_name' => 'body_',
          ],
        ],
      ],
    ], 'custom__xml', $edit);

    // Create a feed and import file.
    $edit = [
      'title[0][value]' => 'Feed 1',
      'plugin[fetcher][source]' => $this->resourcesUrl() . '/test.html',
    ];
    // Save using a dropbutton.
    $this->drupalGet('/feed/add/' . $this->feedType->id());
    $this->submitFormWithDropButton($edit, 'Save');

    // Run import programmatically. Batches don't work well during javascript
    // based tests.
    // @see https://www.drupal.org/project/feeds/issues/2938500#comment-12550186
    $feed = Feed::load(1);
    $feed->import();

    // Assert node values.
    $node1 = Node::load(1);
    $this->assertEquals('I am a title<thing>Stuff</thing>', $node1->getTitle());
    $this->assertEquals('I am a description0', $node1->body->value);
    $node2 = Node::load(2);
    $this->assertEquals('I am a title1', $node2->getTitle());
    $this->assertEquals('I am a description1', $node2->body->value);
    $node3 = Node::load(3);
    $this->assertEquals('I am a title2', $node3->getTitle());
    $this->assertEquals('I am a description2', $node3->body->value);
  }

}
