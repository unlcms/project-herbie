<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;

/**
 * Tests module uninstallation.
 *
 * @group feeds
 */
class FeedsUninstallTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure that the queue table exists by making a fake queue item.
    $queue = new DatabaseQueue($this->randomMachineName(), Database::getConnection());
    $queue->createQueue();
    $queue->createItem(['foo']);
  }

  /**
   * Asserts that there are no Feeds key values.
   */
  protected function assertNoFeedsKeyValues() {
    // Check the collections.
    $num = (int) $this->container->get('database')->select('key_value')
      ->condition('collection', '%feeds%', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(0, $num, 'There are no keyvalue collections with "feeds" in the name.');

    // Check the names.
    $num = (int) $this->container->get('database')->select('key_value')
      ->condition('name', '%feeds%', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(0, $num, 'There are no keyvalues with "feeds" in the name.');
  }

  /**
   * Asserts that there are no Feeds tasks in the queue.
   */
  protected function assertNoFeedsQueueTasks() {
    $this->assertFeedsQueueTasksCount(0, 'There are no Feeds tasks in the queue.');
  }

  /**
   * Asserts that there are no Feeds tasks in the queue.
   *
   * @param int $expected
   *   The number of expected feeds queue tasks.
   * @param string $message
   *   (optional) The message to display.
   */
  protected function assertFeedsQueueTasksCount(int $expected, string $message = NULL) {
    $num = (int) $this->container->get('database')->select('queue')
      ->condition('name', '%feeds%', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();

    if (empty($message)) {
      $message = strtr('There are @num Feeds tasks in the queue. Expected: @expected.', [
        '@num' => $num,
        '@expected' => $expected,
      ]);
    }
    $this->assertEquals($expected, $num, $message);
  }

  /**
   * Tests module uninstallation.
   */
  public function testUninstall() {
    // Confirm that Feeds has been installed.
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('feeds'));

    // Uninstall Feeds.
    $this->container->get('module_installer')->uninstall(['feeds']);
    $this->assertFalse($module_handler->moduleExists('feeds'));

    // Assert that all keyvalues have been cleaned up.
    $this->assertNoFeedsKeyValues();

    // Assert that there are no more tasks in the Feeds queue.
    $this->assertNoFeedsQueueTasks();
  }

  /**
   * Tests module uninstallation after one finished import.
   */
  public function testUninstallAfterImport() {
    // Create a feed type.
    $feed_type = $this->createFeedType();

    // Create a feed and import a file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/atom/entries.atom',
    ]);
    $feed->import();
    $this->assertNodeCount(3);

    // Delete feed and feed type so that the uninstallation can happen.
    $feed->delete();
    $feed_type->delete();

    // Remove fields that are pending deletion.
    field_purge_batch(1);

    // Uninstall Feeds.
    $this->container->get('module_installer')->uninstall(['feeds']);
    $this->assertFalse($this->container->get('module_handler')->moduleExists('feeds'));

    // Assert that all keyvalues have been cleaned up.
    $this->assertNoFeedsKeyValues();

    // Assert that there are no more tasks in the Feeds queue.
    $this->assertNoFeedsQueueTasks();
  }

  /**
   * Tests module uninstallation after scheduling an import.
   */
  public function testUninstallAfterSchedulingAnImport() {
    // Create a feed type.
    $feed_type = $this->createFeedType();

    // Create a feed and schedule an import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/atom/entries.atom',
    ]);
    $feed->startCronImport();
    $this->assertFeedsQueueTasksCount(1);

    // Assert that no nodes have been created yet.
    $this->assertNodeCount(0);

    // Delete feed and feed type so that the uninstallation can happen.
    $feed->delete();
    $feed_type->delete();

    // Remove fields that are pending deletion.
    field_purge_batch(1);

    // Uninstall Feeds.
    $this->container->get('module_installer')->uninstall(['feeds']);
    $this->assertFalse($this->container->get('module_handler')->moduleExists('feeds'));

    // Assert that all keyvalues have been cleaned up.
    $this->assertNoFeedsKeyValues();

    // Assert that there are no more tasks in the Feeds queue.
    $this->assertNoFeedsQueueTasks();
  }

  /**
   * Tests module uninstallation after an import was partially completed.
   */
  public function testUninstallAfterUnfinishedImport() {
    // Create a feed type.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ]);
    // Create a feed and schedule an import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/nodes.csv',
    ]);
    $feed->startCronImport();
    $this->assertFeedsQueueTasksCount(1);

    // Perform a few tasks from the queue. These will be:
    // 1. fetch;
    // 2. parse;
    // 3. process item 1;
    // 4. process item 2;
    // 5. process item 3.
    $this->runQueue('feeds_feed_refresh:' . $feed_type->id(), 5);

    // Assert that 3 nodes have been imported already.
    $this->assertNodeCount(3);

    // And assert 7 remaining tasks (6 process, 1 finish).
    $this->assertFeedsQueueTasksCount(7);

    // Delete feed and feed type so that the uninstallation can happen.
    $feed->delete();
    $feed_type->delete();

    // Remove fields that are pending deletion.
    field_purge_batch(1);

    // Uninstall Feeds.
    $this->container->get('module_installer')->uninstall(['feeds']);
    $this->assertFalse($this->container->get('module_handler')->moduleExists('feeds'));

    // Assert that all keyvalues have been cleaned up.
    $this->assertNoFeedsKeyValues();

    // Assert that there are no more tasks in the Feeds queue.
    $this->assertNoFeedsQueueTasks();
  }

  /**
   * Tests module uninstallation when a feeds task remained stuck in queue.
   */
  public function testUninstallAfterStuckQueueItem() {
    // Create a mocked feed.
    $feed = $this->createMock(FeedInterface::class);

    // Create a fake queue item.
    $this->container->get('queue')->get('feeds_feed_refresh:foo')
      ->createItem([$feed, 'begin', []]);

    // Uninstall Feeds.
    $this->container->get('module_installer')->uninstall(['feeds']);
    $this->assertFalse($this->container->get('module_handler')->moduleExists('feeds'));

    // Assert that all keyvalues have been cleaned up.
    $this->assertNoFeedsKeyValues();

    // Assert that there are no more tasks in the Feeds queue.
    $this->assertNoFeedsQueueTasks();
  }

  /**
   * Tests uninstalling a third party module.
   */
  public function testUninstallModuleWithThirdPartySettings() {
    // Install Feeds test plugin which provides a config schema for third party
    // settings.
    $this->assertTrue($this->container->get('module_installer')->install(['feeds_test_plugin']));

    // Create a feed type and add third party config to it.
    $feed_type = $this->createFeedType();
    $feed_type->setThirdPartySetting('feeds_test_plugin', 'status', TRUE);
    $feed_type->save();

    // Now uninstall the feeds_test_plugin module.
    $this->container->get('module_installer')->uninstall(['feeds_test_plugin']);

    // Flushing all caches is needed because else the testbot can read reload
    // the feed type from cache.
    drupal_flush_all_caches();
    // The testbot uses or can use the cache backed "ApcuBackend" to cache
    // config objects. This cache backend is wrapped inside a backend called
    // "ChainedFastBackend". Based on the docs from that backend, some time
    // needs to pass in order to read data from the database instead of from the
    // APCu cache. Since there is very little time between creating the feed
    // type and uninstalling a module, make sure that the feed type does not
    // exist on the APCu cache.
    if (function_exists('apcu_clear_cache')) {
      apcu_clear_cache();
    }

    // Assert that the feed type no longer exist.
    $this->assertInstanceOf(FeedTypeInterface::class, $this->reloadEntity($feed_type));
  }

}
