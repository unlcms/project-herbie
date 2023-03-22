<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Tests the expire feature.
 *
 * @group feeds
 */
class ExpireTest extends FeedsBrowserTestBase {

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
        'expire' => 3600,
        'skip_hash_check' => TRUE,
      ],
    ]);
  }

  /**
   * Tests expiring items when doing an import in the UI.
   */
  public function testExpireItemsWithBatch() {
    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Reload feed and assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Set import time to be about an hour in the past.
    \Drupal::database()->update('node__feeds_item')
      ->fields([
        'feeds_item_imported' => $this->container->get('datetime.time')->getRequestTime() - 3601,
      ])
      ->execute();

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that one node is removed.
    $feed = $this->reloadFeed($feed);
    $this->assertSession()->responseContains('Expired 1 items.');
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // And assert that the feed is no longer locked.
    $this->assertFalse($feed->isLocked());
  }

  /**
   * Tests expiring items using cron.
   *
   * @todo implement feature for expiring items using cron.
   */
  public function testExpireItemsWithCron() {
    $this->markTestIncomplete('Expiring items on cron runs is not implemented yet');

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

    // Set import time to be about an hour in the past.
    \Drupal::database()->update('node__feeds_item')
      ->fields([
        'feeds_item_imported' => $this->container->get('datetime.time')->getRequestTime() - 3601,
      ])
      ->execute();

    // Run cron again.
    $this->cronRun();

    // Assert that all nodes have been expired.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(0, $feed->getItemCount());
    $this->assertNodeCount(0);

    // And assert that the feed is no longer locked.
    $this->assertFalse($feed->isLocked());
  }

}
