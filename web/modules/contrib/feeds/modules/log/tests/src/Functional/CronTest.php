<?php

namespace Drupal\Tests\feeds_log\Functional;

use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests behavior involving imports on cron.
 *
 * @group feeds_log
 */
class CronTest extends FeedsLogBrowserTestBase {

  /**
   * Tests importing through cron.
   */
  public function test() {
    $feed_type = $this->createFeedType();

    // Set import period to once an hour.
    $feed_type->setImportPeriod(3600);
    $feed_type->save();

    // Create a feed and run cron.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $this->cronRun();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals($this->adminUser->id(), $import_log->uid->target_id);
    $this->assertStringStartsWith('private://feeds/log/1/source/', $import_log->sources->value);

    // Assert that the source was logged with the expected contents.
    $this->assertFileIsReadable($import_log->sources->value);
    $this->assertFileEquals($this->resourcesPath() . '/rss/googlenewstz.rss2', $import_log->sources->value);

    // Assert that 6 log entries have been created.
    $entries = $this->getLogEntries();
    $this->assertCount(6, $entries);

    // Assert that logged items are readable.
    $this->assertFileIsReadable('private://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/5.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/6.json');
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
      'feeds_log' => TRUE,
    ]);

    // Schedule import.
    $feed->startCronImport();

    // Run cron. Five nodes should be imported.
    $this->cronRun();
    $this->assertNodeCount(5);

    // Assert that an import log entity was created, but it has no finish time
    // yet.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals($this->adminUser->id(), $import_log->uid->target_id);
    $this->assertNotEmpty($import_log->start->value);
    $this->assertEmpty($import_log->end->value);
    $this->assertStringStartsWith('private://feeds/log/1/source/', $import_log->sources->value);

    // Assert that 5 log entries have been created so far.
    $entries = $this->getLogEntries();
    $this->assertCount(5, $entries);

    // Run cron again to import the remaining 4 items.
    $this->cronRun();
    $this->assertNodeCount(9);

    // Assert that the import log entity now has a finish time.
    $import_log = $this->reloadEntity($import_log);
    $this->assertNotEmpty($import_log->end->value);

    // Assert that 9 log entries exist now.
    $entries = $this->getLogEntries();
    $this->assertCount(9, $entries);

    // Assert which logged items are created.
    $this->assertFileIsReadable('private://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/5.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/6.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/7.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/8.json');
    $this->assertFileIsReadable('private://feeds/log/1/items/9.json');

    // Assert that no second import log entity was created.
    $this->assertNull(ImportLog::load(2));
  }

}
