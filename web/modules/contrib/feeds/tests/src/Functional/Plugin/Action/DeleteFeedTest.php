<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Action;

use Drupal\feeds\Entity\Feed;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\Action\DeleteFeed
 * @group feeds
 */
class DeleteFeedTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'views',
  ];

  /**
   * Tests applying action "feeds_feed_delete_action" on feed entities.
   */
  public function test() {
    // Add a feed type.
    $feed_type = $this->createFeedType();

    // Create a few feeds.
    for ($i = 1; $i <= 3; $i++) {
      $this->createFeed($feed_type->id(), [
        'title' => 'My feed ' . $i,
        'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      ]);
    }

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');

    // Select the first two feeds.
    $edit = [];
    for ($i = 0; $i < 2; $i++) {
      $this->assertSession()->fieldExists('edit-feeds-feed-bulk-form-' . $i);
      $edit["feeds_feed_bulk_form[$i]"] = TRUE;
    }

    // Delete the selected feeds.
    $edit += ['action' => 'feeds_feed_delete_action'];
    $this->submitForm($edit, 'Apply to selected items');

    // Assert a confirmation page is shown.
    $this->assertSession()->pageTextContains('Are you sure you want to delete these items?');
    $this->submitForm([], 'Delete');

    // Assert that feed 1 and feed 2 are deleted, but feed 3 is not.
    $this->assertNull(Feed::load(1));
    $this->assertNull(Feed::load(2));
    $this->assertInstanceOf(Feed::class, Feed::load(3));
    $this->assertSession()->pageTextContains('Deleted 2 feeds.');
  }

}
