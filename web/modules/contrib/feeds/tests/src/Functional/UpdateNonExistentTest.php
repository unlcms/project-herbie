<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Component\Utility\Xss;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Tests the feature of updating items that are no longer available in the feed.
 *
 * @group feeds
 */
class UpdateNonExistentTest extends FeedsBrowserTestBase {

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
   * Tests 'Unpublish non-existent' option using a Batch.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get unpublished when the 'update_non_existent' setting is set to
   * 'entity:unpublish_action:node' and when performing an import using the UI.
   */
  public function testUnpublishNonExistentItemsWithBatch() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Reload feed and assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Created 6 Article items.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that one node was unpublished.
    $node = $this->getNodeByTitle('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters');
    $this->assertFalse($node->isPublished());

    // Manually publish the node.
    $node->status = 1;
    $node->setTitle('Lorem');
    $node->save();
    $this->assertTrue($node->isPublished(), 'Node is published');

    // Import the same file again to ensure that the node does not get
    // unpublished again (since the node was already unpublished during the
    // previous import).
    $this->batchImport($feed);
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->isPublished(), 'Node is not updated');

    // Re-import the original feed to ensure the unpublished node is updated,
    // even though the item is the same since the last time it was available in
    // the feed. Fact is that the node was not available in the previous import
    // and that should be seen as a change.
    $feed = $this->reloadFeed($feed);
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->batchImport($feed);
    $node = $this->reloadEntity($node);
    $this->assertSession()->responseContains('Updated 1 Article.');
    static::assertEquals('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters', $node->getTitle());
  }

  /**
   * Tests 'Delete non-existent' option using a Batch.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get deleted when the 'update_non_existent' setting is set to
   * '_delete' and when performing an import using the UI.
   */
  public function testDeleteNonExistentItemsWithBatch() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Created 6 Article items.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that one node is removed.
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Cleaned 1 Article.');
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // Re-import the original feed to import the removed node again.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->batchImport($feed);
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Created 1 Article.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests 'Unpublish non-existent' option using cron.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get unpublished when the 'update_non_existent' setting is set to
   * 'entity:unpublish_action:node' and when performing an import using cron.
   */
  public function testUnpublishNonExistentItemsWithCron() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    // Set the import period to run as often as possible.
    $this->feedType->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Run cron to import.
    $this->cronRun();

    // Reload feed and assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->cronRun();

    // Assert that one node was unpublished.
    $node = $this->getNodeByTitle('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters');
    $this->assertFalse($node->isPublished());

    // Manually publish the node.
    $node->status = 1;
    $node->setTitle('Lorem');
    $node->save();
    $this->assertTrue($node->isPublished(), 'Node is published');

    // Import the same file again to ensure that the node does not get
    // unpublished again (since the node was already unpublished during the
    // previous import).
    $this->cronRun();
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->isPublished(), 'Node is not updated');

    // Re-import the original feed to ensure the unpublished node is updated,
    // even though the item is the same since the last time it was available in
    // the feed. Fact is that the node was not available in the previous import
    // and that should be seen as a change.
    $feed = $this->reloadFeed($feed);
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->cronRun();
    $node = $this->reloadEntity($node);
    static::assertEquals('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters', $node->getTitle());
  }

  /**
   * Tests 'Delete non-existent' option using cron.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get deleted when the 'update_non_existent' setting is set to
   * '_delete' and when performing an import using cron.
   */
  public function testDeleteNonExistentItemsWithCron() {
    // Set 'update_non_existent' setting to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    // Set the import period to run as often as possible.
    $this->feedType->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->cronRun();

    // Assert that one node is removed.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // Re-import the original feed to import the removed node again.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->cronRun();
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests if the right items get cleaned with running multithreaded imports.
   */
  public function testMultithreadImport() {
    // Set 'update_non_existent' setting to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    // Set the import period to run as often as possible.
    $this->feedType->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->startCronImport();

    // Create queue.
    $queue_name = 'feeds_feed_refresh:' . $this->feedType->id();
    $queue = $this->container->get('queue')->get($queue_name);
    $queue->createQueue();
    $queue_worker = $this->container->get('plugin.manager.queue_worker')->createInstance($queue_name);

    // Process first three queue items. The first item is expected to be the
    // "fetch" item, the second a "parse" item.
    for ($i = 0; $i < 3; $i++) {
      $item = $queue->claimItem();

      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }

    // Listen to process event.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::PROCESS, [$this, 'onProcess'], FeedsEvents::AFTER);

    // Process another item.
    $item = $queue->claimItem();
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    // Process remaining items.
    while ($item = $queue->claimItem()) {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }

    // Assert that only one node is removed.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);
  }

  /**
   * Tests cleaning when using a non-existing action plugin.
   *
   * When upgrading from Drupal 8 to Drupal 9, it is possible that the
   * configured action for the 'update_non_existent' setting no longer exists.
   * In this case, we want the import to fail gracefully, not with a fatal
   * error.
   */
  public function testWithNonExistentActionPlugin() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'foo';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Reload feed and assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Created 6 Article items.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that cleaning failed.
    $page_text = Xss::filter($this->getSession()->getPage()->getContent(), []);
    $this->assertStringContainsString('Cleaning Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters failed because of non-existing action plugin foo.', $page_text);

    // Try to import again and assert that the same error message appears.
    $this->batchImport($feed);
    $page_text = Xss::filter($this->getSession()->getPage()->getContent(), []);
    $this->assertStringContainsString('Cleaning Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters failed because of non-existing action plugin foo.', $page_text);
  }

  /**
   * Acts on processing a single item.
   *
   * @param \Drupal\feeds\Event\ProcessEvent $event
   *   The process event.
   */
  public function onProcess(ProcessEvent $event) {
    // Claim another queue item.
    $feed_type_id = $event->getFeed()->getType()->id();
    $queue_name = 'feeds_feed_refresh:' . $feed_type_id;
    $queue = $this->container->get('queue')->get($queue_name);
    $queue_worker = $this->container->get('plugin.manager.queue_worker')->createInstance($queue_name);

    $item = $queue->claimItem();
    if ($item) {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }
  }

}
