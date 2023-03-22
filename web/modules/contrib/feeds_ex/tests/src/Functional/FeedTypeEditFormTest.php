<?php

namespace Drupal\Tests\feeds_ex\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests editing the feed type edit form.
 *
 * @group feeds_ex
 */
class FeedTypeEditFormTest extends FeedsExBrowserTestBase {

  /**
   * Tests if configuration is preserved after saving the feed type form.
   */
  public function testFeedTypeEdit() {
    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'parser' => 'xml',
      'parser_configuration' => [
        'context' => [
          'value' => '//item',
        ],
        'sources' => [
          'guid' => [
            'label' => 'guid',
            'value' => 'guid',
          ],
          'title' => [
            'label' => 'title',
            'value' => 'title',
          ],
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

    // Save feed type.
    $this->drupalGet('/admin/structure/feeds/manage/' . $this->feedType->id());
    // @todo figure out why Drupal cannot find user 0:
    // > "The referenced entity (user: 0) does not exist."
    $edit = [
      'processor_configuration[owner_id]' => '',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Assert that the config has remained intact by doing an import now.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesUrl() . '/content.xml',
    ]);
    $this->batchImport($feed);
    $this->assertSession()->pageTextContains('Created 2 Article items.');

    // Assert node values.
    $node1 = Node::load(1);
    $this->assertEquals('1', $node1->feeds_item->guid);
    $this->assertEquals('Lorem ipsum', $node1->getTitle());
    $node2 = Node::load(2);
    $this->assertEquals('2', $node2->feeds_item->guid);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node2->getTitle());
  }

}
