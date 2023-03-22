<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the feature of updating items that are no longer available in the feed.
 *
 * @group feeds
 */
class UpdateNonExistentTest extends FeedsKernelTestBase {

  use NodeCreationTrait;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);
  }

  /**
   * Asserts that no items exist on the clean list for the given feed.
   *
   * @param int $expected_count
   *   The amount of expected items on the clean list.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to check the clean list for.
   */
  protected function assertCleanListCount($expected_count, FeedInterface $feed) {
    $count = $this->container->get('database')
      ->select('feeds_clean_list')
      ->fields('feeds_clean_list', [])
      ->condition('feed_id', $feed->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals($expected_count, $count);
  }

  /**
   * Asserts that no items exist on the clean list for the given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to check the clean list for.
   */
  protected function assertCleanListEmpty(FeedInterface $feed) {
    $this->assertCleanListCount(0, $feed);
  }

  /**
   * Tests 'Unpublish non-existent' option.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get unpublished when the 'update_non_existent' setting is set to
   * 'entity:unpublish_action:node'.
   */
  public function testUnpublishNonExistentItems() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    // Assert that one was unpublished.
    $node = $this->getNodeByTitle('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters');
    $this->assertFalse($node->isPublished());

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);

    // Manually publish the node.
    $node->status = 1;
    $node->setTitle('Lorem');
    $node->save();
    $this->assertTrue($node->isPublished(), 'Node is published');

    // Import the same file again to ensure that the node does not get
    // unpublished again (since the node was already unpublished during the
    // previous import).
    $feed->import();
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->isPublished(), 'Node is not updated');

    // Re-import the original feed to ensure the unpublished node is updated,
    // even though the item is the same since the last time it was available in
    // the feed. Fact is that the node was not available in the previous import
    // and that should be seen as a change.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $feed->import();
    $node = $this->reloadEntity($node);
    static::assertEquals('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters', $node->getTitle());
  }

  /**
   * Tests 'Delete non-existent' option.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get deleted when the 'update_non_existent' setting is set to
   * '_delete'.
   */
  public function testDeleteNonExistentItems() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    // Assert that one node is removed.
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // Assert that the clean list is empty for the feed.
    $this->assertCleanListEmpty($feed);

    // Re-import the original feed to import the removed node again.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $feed->import();
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests if the feeds clean list gets empty after clearing states.
   */
  public function testEmptyCleanListAfterClearingStates() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Add two records to the feeds_clean_list table for this feed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    $clean_state->setList([123, 456]);
    $this->assertCleanListCount(2, $feed);

    // Clear states.
    $feed->clearStates();

    // Assert that the clean list is now empty for this feed.
    $this->assertCleanListEmpty($feed);
  }

  /**
   * Tests if the feeds clean list gets empty after deleting feed.
   *
   * There could exist records on the clean list if an import ends abruptly, for
   * example.
   */
  public function testEmptyCleanListAfterDeletingFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Add two records to the feeds_clean_list table for this feed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    $clean_state->setList([123, 456]);
    $this->assertCleanListCount(2, $feed);

    // Delete the feed.
    $feed->delete();

    // Assert that the clean list is now empty for this feed.
    $this->assertCleanListEmpty($feed);
  }

}
