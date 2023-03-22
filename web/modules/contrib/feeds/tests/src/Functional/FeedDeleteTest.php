<?php

namespace Drupal\Tests\feeds\Functional;

/**
 * Tests deleting a feed using the UI.
 *
 * @group feeds
 */
class FeedDeleteTest extends FeedsBrowserTestBase {

  /**
   * Tests deleting a feed using the UI.
   */
  public function testFeedDelete() {
    // Add a feed type.
    $feed_type = $this->createFeedType();

    // Add a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Now try to delete this feed.
    $this->drupalGet('/feed/1/delete');
    $this->submitForm([], 'Delete');

    // Ensure that no errors are shown.
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error.');

    // Ensure that the feed no now longer exists.
    $this->assertNull($this->reloadEntity($feed));
  }

  /**
   * Tests deleting a feed that has imported items.
   */
  public function testFeedDeleteWithImportedItems() {
    // Create a feed type.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
    ]);

    // Add a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Import data.
    $feed->import();
    $this->assertNodeCount(6);

    // Now try to delete this feed.
    $this->drupalGet('/feed/1/delete');
    $this->submitForm([], 'Delete');

    // Ensure that the feed now no longer exists.
    $this->assertNull($this->reloadEntity($feed));

    // Ensure that no errors are shown.
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error.');

    // And ensure that the imported content still exists.
    $this->assertNodeCount(6);
  }

  /**
   * Tests deleting a feed whose feed type no longer exists.
   */
  public function testOrphanedFeedDelete() {
    // Add a feed type.
    $feed_type = $this->createFeedType();

    // Add a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Programmatically delete the feed type. The feed is now orphaned.
    $feed_type->delete();

    // Now try to delete this feed.
    $this->drupalGet('/feed/1/delete');
    $this->submitForm([], 'Delete');

    // Ensure that no errors are shown.
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error.');

    // Ensure that the feed now no longer exists.
    $this->assertNull($this->reloadEntity($feed));
  }

}
