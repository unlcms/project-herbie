<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Fetcher;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\DirectoryFetcher
 * @group feeds
 */
class DirectoryFetcherTest extends FeedsKernelTestBase {

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
    ]);
  }

  /**
   * Tests importing a feed using the directory fetcher.
   */
  public function testImportSingleFile() {
    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

}
