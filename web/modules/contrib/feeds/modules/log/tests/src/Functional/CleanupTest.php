<?php

namespace Drupal\Tests\feeds_log\Functional;

use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests for cleaning up log entries.
 *
 * @group feeds_log
 */
class CleanupTest extends FeedsLogBrowserTestBase {

  /**
   * The number of seconds in a week.
   *
   * @var int
   */
  const SECONDS_IN_WEEK = 604800;

  /**
   * Tests that logged imports older than a week get deleted.
   */
  public function testCleanupOldLogs() {
    // Import a few times.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'feeds_log' => TRUE,
    ]);
    $feed->import();
    $feed->import();

    $feed_type2 = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $feed2 = $this->createFeed($feed_type2->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'feeds_log' => TRUE,
    ]);
    $feed2->import();
    $feed2->import();

    // For two logged imports, manually set the finish time to be a week in the
    // past.
    $import_log1 = ImportLog::load(1);
    $import_log1->end->value = $import_log1->end->value - static::SECONDS_IN_WEEK - 1;
    $import_log1->save();
    $import_log3 = ImportLog::load(3);
    $import_log3->end->value = $import_log3->end->value - static::SECONDS_IN_WEEK - 1;
    $import_log3->save();

    // Run cron.
    $this->cronRun();

    // Assert that import logs 1 and 3 are deleted, but 2 and 4 still exist.
    $this->assertNull($this->reloadEntity($import_log1));
    $this->assertInstanceOf(ImportLog::class, ImportLog::load(2));
    $this->assertNull($this->reloadEntity($import_log3));
    $this->assertInstanceOf(ImportLog::class, ImportLog::load(4));
  }

  /**
   * Tests that the configured age limit is respected.
   */
  public function testCleanupOldLogsWithChangedAgeLimit() {
    // Import a few times.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'feeds_log' => TRUE,
    ]);
    $feed->import();
    $feed->import();

    // For two logged imports, manually change the finish time. The first logged
    // import is set longer ago than the second.
    $import_log1 = ImportLog::load(1);
    $import_log1->end->value = $import_log1->end->value - 201;
    $import_log1->save();
    $import_log2 = ImportLog::load(2);
    $import_log2->end->value = $import_log2->end->value - 101;
    $import_log2->save();

    // Assert that with the default setting, the logs don't get cleaned up yet.
    $this->cronRun();
    $this->assertInstanceOf(ImportLog::class, $this->reloadEntity($import_log1));
    $this->assertInstanceOf(ImportLog::class, $this->reloadEntity($import_log2));

    // Now set the age limit to 200 seconds. This should result into the first
    // logged import to be cleaned up because that finished 201 seconds ago, but
    // the second logged import that happened only 101 seconds ago, should still
    // exist.
    $this->config('feeds_log.settings')
      ->set('age_limit', 200)
      ->save();

    // Run cron again and assert that the first logged import has been cleaned
    // up, but the second has not.
    $this->cronRun();
    $this->assertNull($this->reloadEntity($import_log1));
    $this->assertInstanceOf(ImportLog::class, $this->reloadEntity($import_log2));
  }

  /**
   * Tests that old logs get cleaned up for unfinished imports.
   */
  public function testCleanupLogsForImportsThatNeverFinished() {
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ]);
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/nodes.csv',
      'feeds_log' => TRUE,
    ]);

    // Start an import for this feed, but do not finish it.
    // Fetch, parse and process a single item.
    $feed->startCronImport();
    $this->runQueue('feeds_feed_refresh:' . $feed_type->id(), 3);

    // Assert that one item has been imported.
    $this->assertNodeCount(1);

    // Manually set the start import time to be more than a week ago.
    $import_log1 = ImportLog::load(1);
    $import_log1->start->value = $import_log1->start->value - static::SECONDS_IN_WEEK - 1;
    $import_log1->save();

    // Unlock the feed, clear the queue and clear states.
    $feed->unlock();
    $feed->clearStates();
    $this->container->get('database')->truncate('queue');

    // Run cron and assert that the logged import is not cleaned up yet.
    $this->cronRun();
    $this->assertInstanceOf(ImportLog::class, $this->reloadEntity($import_log1));

    // Start a new import and run cron twice. A new import log is only created
    // *after* checks for cleanups have ran.
    $feed->startCronImport();
    $this->cronRun();
    $this->cronRun();

    // Assert that a second logged import exists.
    $import_log2 = ImportLog::load(2);
    $this->assertInstanceOf(ImportLog::class, $import_log2);

    // Assert that the first logged import is cleaned up, since a newer import
    // exists.
    $this->assertNull($this->reloadEntity($import_log1));
  }

  /**
   * Tests that logs don't get cleaned up when age limit is 0.
   */
  public function testSkipAutoCleanup() {
    // Import a feed.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    // Import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // For the logged import, manually set the finish time to be a week in the
    // past.
    $import_log = ImportLog::load(1);
    $import_log->end->value = $import_log->end->value - static::SECONDS_IN_WEEK;

    // Configure to not cleanup logs.
    $this->config('feeds_log.settings')
      ->set('age_limit', 0)
      ->save();

    // Run cron.
    $this->cronRun();

    // Assert that the logged import still exists.
    $this->assertInstanceOf(ImportLog::class, $this->reloadEntity($import_log));
  }

}
