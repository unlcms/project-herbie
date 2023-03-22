<?php

namespace Drupal\Tests\feeds_log\Kernel;

use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests for cleaning up log entries.
 *
 * @group feeds_log
 */
class CleanupTest extends FeedsLogKernelTestBase {

  /**
   * Tests cleaning up logs for a single import.
   */
  public function testCleanup() {
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

    // Assert that 2 nodes have been created.
    $this->assertNodeCount(2);

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that 2 log entries exist.
    $this->assertCount(2, $this->getLogEntries());

    // Assert that log files exist on the filesystem.
    $this->assertFileIsReadable('public://feeds/log/1/source/content.csv');
    $this->assertFileIsReadable('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');

    // Now delete the log.
    $import_log->delete();

    // And assert things have been cleaned up.
    $this->assertCount(0, $this->getLogEntries());

    // Assert that log files no longer exist on the filesystem.
    $this->assertFileDoesNotExist('public://feeds/log/1/source/content.csv');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/2.json');
  }

  /**
   * Tests cleaning up all logs for a single feed.
   */
  public function testCleanupAllLogsWhenDeletingFeed() {
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

    // Import again.
    $feed->import();

    // Assert that two import log entities were created.
    $import_log1 = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log1->feed->target_id);
    $import_log2 = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log2->feed->target_id);

    // Assert that 4 log entries exist.
    $this->assertCount(4, $this->getLogEntries());

    // Assert that log files exist on the filesystem.
    $this->assertFileIsReadable('public://feeds/log/1/source/content.csv');
    $this->assertFileIsReadable('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('public://feeds/log/2/source/content.csv');
    $this->assertFileIsReadable('public://feeds/log/2/items/3.json');
    $this->assertFileIsReadable('public://feeds/log/2/items/4.json');

    // Now delete the feed.
    $feed->delete();

    // Assert that log entities no longer exist.
    $this->assertNull($this->reloadEntity($import_log1));
    $this->assertNull($this->reloadEntity($import_log2));

    // And assert things have been cleaned up.
    $this->assertCount(0, $this->getLogEntries());

    // Assert that log files no longer exist on the filesystem.
    $this->assertFileDoesNotExist('public://feeds/log/1/source/content.csv');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/2.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/source/content.csv');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/3.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/4.json');
  }

}
