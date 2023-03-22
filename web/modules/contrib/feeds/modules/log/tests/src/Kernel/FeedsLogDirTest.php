<?php

namespace Drupal\Tests\feeds_log\Kernel;

use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests regarding the feeds log directory.
 *
 * @group feeds_log
 */
class FeedsLogDirTest extends FeedsLogKernelTestBase {

  /**
   * Tests if files can get logged to a different place.
   */
  public function testWithOtherFeedsLogDir() {
    // Set a different directory for storing the logs.
    $this->config('feeds_log.settings')
      ->set('log_dir', 'public://logs/foo/bar')
      ->save();

    // Create a feed and import.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert location where files has been logged.
    $import_log = ImportLog::load(1);
    $this->assertEquals('public://logs/foo/bar/1/source/content.csv', $import_log->sources->value);
    $this->assertFileIsReadable('public://logs/foo/bar/1/source/content.csv');
    $this->assertFileIsReadable('public://logs/foo/bar/1/items/1.json');
    $this->assertFileIsReadable('public://logs/foo/bar/1/items/2.json');
  }

}
