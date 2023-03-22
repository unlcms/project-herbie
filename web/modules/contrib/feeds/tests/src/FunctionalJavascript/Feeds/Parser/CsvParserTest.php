<?php

namespace Drupal\Tests\feeds\FunctionalJavascript\Feeds\Parser;

use Drupal\feeds\Entity\Feed;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\CsvParser
 * @group feeds
 */
class CsvParserTest extends ParserTestBase {

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'csv';

  /**
   * Tests adding a custom mapping source.
   */
  public function testMapCustomSource() {
    // Create a text field called 'alpha'.
    $this->createFieldWithStorage('field_alpha');

    // Create a filter format.
    $format = FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ]);
    $format->save();

    // Allow admin user to use this format.
    $rid = $this->createRole([$format->getPermissionName()]);
    $this->adminUser->addRole($rid);
    $this->adminUser->save();

    // Add mappings to feed item, title, body, alpha.
    $this->addMappings($this->feedType->id(), [
      [
        'target' => 'feeds_item',
        'map' => [
          'guid' => [
            'value' => 'guid',
            'machine_name' => 'guid_',
          ],
        ],
        'unique' => ['guid' => TRUE],
      ],
      [
        'target' => 'title',
        'map' => [
          'value' => [
            'value' => 'title',
            'machine_name' => 'title_',
          ],
        ],
      ],
      [
        'target' => 'body',
        'map' => [
          'value' => [
            'value' => 'body',
            'machine_name' => 'body_',
          ],
        ],
        'settings' => ['format' => $format->id()],
      ],
      [
        'target' => 'field_alpha',
        'map' => [
          'value' => [
            'value' => 'alpha',
            'machine_name' => 'alpha_',
          ],
        ],
      ],
    ], 'custom__csv');

    // Create a feed.
    $edit = [
      'title[0][value]' => 'Feed 1',
      'plugin[fetcher][source]' => $this->resourcesUrl() . '/csv/content.csv',
    ];
    // Save using a dropbutton.
    // @todo remove dropbutton on the UI. Just make it two buttons.
    $this->drupalGet('/feed/add/' . $this->feedType->id());
    $this->submitFormWithDropButton($edit, 'Save');

    // Run import programmatically. Batches don't work well during javascript
    // based tests.
    // @see https://www.drupal.org/project/feeds/issues/2938500#comment-12550186
    $feed = Feed::load(1);
    $feed->import();

    // Assert node values.
    $node1 = Node::load(1);
    $this->assertEquals('1', $node1->get('feeds_item')->getItemByFeed($feed)->guid);
    $this->assertEquals('Lorem ipsum', $node1->getTitle());
    $this->assertEquals('Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.', $node1->body->value);
    $this->assertEquals('Lorem', $node1->field_alpha->value);
    $node2 = Node::load(2);
    $this->assertEquals('2', $node2->get('feeds_item')->getItemByFeed($feed)->guid);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node2->getTitle());
    $this->assertEquals('Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat.', $node2->body->value);
    $this->assertEquals('Ut wisi', $node2->field_alpha->value);
  }

}
