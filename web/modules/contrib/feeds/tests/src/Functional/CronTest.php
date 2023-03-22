<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds_test_events\EventSubscriber\FeedsSubscriber;
use Drupal\node\Entity\Node;

/**
 * Tests behavior involving periodic import.
 *
 * @group feeds
 */
class CronTest extends FeedsBrowserTestBase {

  /**
   * Asserts that the given nodes have the expected titles.
   *
   * @param array $expected_titles
   *   The expected titles that the nodes should have, keyed by node ID.
   */
  protected function assertNodeTitles(array $expected_titles) {
    foreach ($expected_titles as $nid => $expected_title) {
      $node = Node::load($nid);
      $this->assertEquals($expected_title, $node->title->value);
    }
  }

  /**
   * Tests importing through cron.
   */
  public function test() {
    $feed_type = $this->createFeedType();

    // Set import period to once an hour and unset unique target.
    $feed_type->setImportPeriod(3600);
    $mappings = $feed_type->getMappings();
    unset($mappings[0]['unique']);
    $feed_type->setMappings($mappings);
    $feed_type->save();

    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Verify initial values.
    $feed = $this->reloadEntity($feed);
    $this->assertEquals(0, $feed->getImportedTime());
    $this->assertEquals(0, $feed->getNextImportTime());
    $this->assertEquals(0, $feed->getItemCount());

    // Cron should import some nodes.
    // Clear the download cache so that the http fetcher doesn't trick us.
    \Drupal::cache('feeds_download')->deleteAll();
    sleep(1);
    $this->cronRun();
    $feed = $this->reloadEntity($feed);

    $this->assertEquals(6, $feed->getItemCount());
    $imported = $feed->getImportedTime();
    $this->assertTrue($imported > 0);
    $this->assertEquals($imported + 3600, $feed->getNextImportTime());

    // Nothing should change on this cron run.
    \Drupal::cache('feeds_download')->deleteAll();
    sleep(1);
    $this->cronRun();
    $feed = $this->reloadEntity($feed);

    $this->assertEquals(6, $feed->getItemCount());
    $this->assertEquals($imported, $feed->getImportedTime());
    $this->assertEquals($imported + 3600, $feed->getNextImportTime());

    // Check that items import normally.
    \Drupal::cache('feeds_download')->deleteAll();
    sleep(1);
    $this->drupalGet('feed/' . $feed->id() . '/import');
    $this->submitForm([], t('Import'));
    $feed = $this->reloadEntity($feed);

    $manual_imported_time = $feed->getImportedTime();
    $this->assertEquals(12, $feed->getItemCount());
    $this->assertTrue($manual_imported_time > $imported);
    $this->assertEquals($feed->getImportedTime() + 3600, $feed->getNextImportTime());

    // Change the next time so that the feed should be scheduled. Then, disable
    // it to ensure the status is respected.
    // Nothing should change on this cron run.
    $feed = $this->reloadEntity($feed);
    $feed->set('next', 0);
    $feed->setActive(FALSE);
    $feed->save();

    \Drupal::cache('feeds_download')->deleteAll();
    sleep(1);
    $this->cronRun();
    $feed = $this->reloadEntity($feed);

    $this->assertEquals(12, $feed->getItemCount());
    $this->assertEquals($manual_imported_time, $feed->getImportedTime());
    $this->assertEquals(0, $feed->getNextImportTime());
  }

  /**
   * Tests importing a source that needs multiple cron runs to complete.
   */
  public function testImportSourceWithMultipleCronRuns() {
    // Install module that alters how many items can be processed per cron run.
    // By default, the module limits the number of processable items to 5.
    $this->container->get('module_installer')->install([
      'feeds_test_files',
      'feeds_test_multiple_cron_runs',
    ]);
    $this->rebuildContainer();

    // Create a feed type. Do not set a column as unique.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'fetcher' => 'http',
      'fetcher_configuration' => [],
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);

    // Select a file that contains 9 items.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => \Drupal::request()->getSchemeAndHttpHost() . '/testing/feeds/nodes.csv',
    ]);

    // Schedule import.
    $feed->startCronImport();

    // Run cron. Five nodes should be imported.
    $this->cronRun();
    $this->assertNodeCount(5);

    // Now change the source to test if the source is not refetched while the
    // import hasn't been finished yet. The following is different:
    // - Items 1 and 4 changed.
    // - Items 2 and 7 were removed.
    \Drupal::state()->set('feeds_test_nodes_last_modified', strtotime('Sun, 30 Mar 2016 10:19:55 GMT'));
    \Drupal::cache('feeds_download')->deleteAll();

    // Run cron again. Another four nodes should be imported.
    $this->cronRun();
    $this->assertNodeCount(9);
  }

  /**
   * Tests that no concurrent imports can happen if lock timeout exceeds.
   */
  public function testNoConcurrentImportsUponLockTimeout() {
    // Install the module that alters how many items can be processed per cron
    // run.
    $this->container->get('module_installer')->install([
      'feeds_test_events',
      'feeds_test_multiple_cron_runs',
    ]);
    $this->rebuildContainer();

    // There are 9 items to import. Set the limit of processable items to 4, so
    // that three cron runs are needed to complete the import.
    $this->container->get('config.factory')
      ->getEditable('feeds_test_multiple_cron_runs.settings')
      ->set('import_queue_time', 4)
      ->save();

    // Set the lock timeout to just two seconds.
    $this->container->get('config.factory')
      ->getEditable('feeds.settings')
      ->set('lock_timeout', 2)
      ->save();

    // Create a feed type. Do not set a column as unique, so that a next import
    // would create duplicates. This test however expects that only one import
    // would run in total.
    // Set periodic import to run as often as possible.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);
    $feed_type->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $feed_type->save();

    // Select a file that contains 9 items.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/nodes.csv',
    ]);

    // Run cron. Four nodes should be imported.
    $this->cronRun();
    $this->assertNodeCount(4);

    // Assert which items are imported.
    $expected_titles = [
      1 => 'Ut wisi enim ad minim veniam',
      2 => 'Duis autem vel eum iriure dolor',
      3 => 'Nam liber tempor',
      4 => 'Typi non habent""',
    ];
    $this->assertNodeTitles($expected_titles);

    // Run cron again. Another four nodes should be imported.
    $this->cronRun();
    $this->assertNodeCount(8);

    // Assert which items are imported.
    $expected_titles = [
      5 => 'Lorem ipsum',
      6 => 'Investigationes demonstraverunt',
      7 => 'Claritas est etiam',
      8 => 'Mirum est notare',
    ];
    $this->assertNodeTitles($expected_titles);

    // Finally, the last cron run should import the last node.
    $this->cronRun();

    $this->assertNodeCount(9);

    // Assert which items are imported.
    $expected_titles = [
      9 => 'Eodem modo typi',
    ];
    $this->assertNodeTitles($expected_titles);

    // Assert that the import has only ran once (fetch happened only once).
    $events = \Drupal::state()->get('feeds_test_events');
    $events_count = array_count_values($events);
    $this->assertSame(1, $events_count[FeedsSubscriber::class . '::onInitImport(fetch)']);
    $this->assertSame(1, $events_count[FeedsSubscriber::class . '::onFinish']);

    // Assert that the queue is empty.
    $this->assertQueueItemCount(0, 'feeds_feed_refresh:' . $feed_type->id());
  }

  /**
   * Tests that a cron run does not fail after deleting a feed type.
   *
   * When a feed is using periodic import, an import for that feed gets
   * eventually triggered on a cron run. But when the feed's feed type no longer
   * exists by then, an import for it should not run and an error should be
   * logged about that feed.
   */
  public function testDeleteFeedTypeForWhichImportIsScheduled() {
    // Create a feed type and a feed.
    $feed_type = $this->createFeedType([
      'id' => 'foo',
    ]);
    $feed_type->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $feed_type->save();

    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Now delete the feed type.
    $feed_type->delete();

    // And run cron. The cron run should not fail. No import should happen.
    $this->cronRun();
    $this->assertNodeCount(0);

    // Assert that an exception gets thrown upon trying to start an import.
    $feed = $this->reloadEntity($feed);
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('The feed type "foo" for feed 1 no longer exists.');
    $feed->startCronImport();
  }

  /**
   * Tests that an unchanged feed finishes an import correctly.
   *
   * When a source gets fetched, but it is unchanged, the import gets aborted
   * early. In such case we want that:
   * - the fetch tasks no longer remains on the queue;
   * - the feed gets unlocked so a new import can be done.
   */
  public function testUnchangedFeedImport() {
    // Install module that will throw a 304 when the same data is fetched again.
    $this->container->get('module_installer')->install(['feeds_test_files']);
    $this->rebuildContainer();

    // Create a feed type. Do not set a column as unique.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'fetcher' => 'http',
      'fetcher_configuration' => [],
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);
    $queue_name = 'feeds_feed_refresh:' . $feed_type->id();

    // Create a feed that contains 9 items.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => \Drupal::request()->getSchemeAndHttpHost() . '/testing/feeds/nodes.csv',
    ]);

    // Schedule import.
    $feed->startCronImport();
    $this->assertTrue($feed->isLocked());
    $this->assertQueueItemCount(1, $queue_name);

    // Run cron. Nine nodes should be imported.
    $this->cronRun();
    $this->assertNodeCount(9);

    // Check that the queue is empty and that the feed is unlocked.
    $this->assertQueueItemCount(0, $queue_name);
    $feed = $this->reloadEntity($feed);
    $this->assertFalse($feed->isLocked());
    // Unlock the feed manually again, since it still exists in memory.
    // @see \Drupal\Core\Lock\DatabaseLockBackend::acquire()
    $feed->unlock();

    // Schedule another import and run cron again. No nodes should be imported.
    // The total should remain 9.
    $feed->startCronImport();
    $this->assertTrue($feed->isLocked());

    $this->assertQueueItemCount(1, $queue_name);
    $this->cronRun();
    $this->assertNodeCount(9);

    // Check that the queue is empty and that the feed is unlocked.
    $this->assertQueueItemCount(0, $queue_name);
    $feed = $this->reloadEntity($feed);
    $this->assertFalse($feed->isLocked());
  }

}
