<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Fetcher;

use Drupal\feeds\Entity\Feed;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\UploadFetcher
 * @group feeds
 */
class UploadFetcherTest extends FeedsBrowserTestBase {

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
      'fetcher' => 'upload',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
    ]);
  }

  /**
   * Tests importing a feed using the upload fetcher.
   */
  public function testImportSingleFile() {
    // Create feed and import.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[plugin_fetcher_source]' => \Drupal::service('file_system')->realpath($this->resourcesPath() . '/rss/googlenewstz.rss2'),
    ];
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->submitForm($edit, t('Save and import'));

    // Load feed.
    $feed = Feed::load(1);

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

}
