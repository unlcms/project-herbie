<?php

namespace Drupal\Tests\feeds\Functional;

/**
 * Tests the feed listing page.
 *
 * @group feeds
 */
class FeedListBuilderTest extends FeedsBrowserTestBase {

  /**
   * Tests the feed listing page with admin privileges.
   */
  public function testUi() {
    // Add a feed type.
    $feed_type = $this->createFeedType();

    // Add a feed.
    $this->createFeed($feed_type->id(), [
      'title' => 'My feed',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the feed listed with the expected links.
    $session->pageTextContains('My feed');
    $session->linkByHrefExists('/feed/1');
    $session->linkExists('Edit');
    $session->linkByHrefExists('/feed/1/edit');
    $session->linkExists('Import');
    $session->linkByHrefExists('/feed/1/import');
    $session->linkExists('Import in background');
    $session->linkByHrefExists('/feed/1/schedule-import');
    $session->linkExists('Delete items');
    $session->linkByHrefExists('/feed/1/delete-items');
  }

  /**
   * Tests the feed listing page when there are no feeds yet.
   */
  public function testUiWithNoFeeds() {
    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();
  }

  /**
   * Tests the feed listing page for an user who may only view feeds.
   */
  public function testUiWithOnlyViewPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add two feeds.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Login as an user who may only view the first feed.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'view ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the first feed listed with the expected links.
    $session->pageTextContains('My feed 1');
    $session->linkByHrefExists('/feed/1');

    // Assert that the second feed is *not* listed.
    $session->pageTextNotContains('My feed 2');
    $session->linkByHrefNotExists('/feed/2');

    // Assert that the following links do *not* exist for both feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
  }

  /**
   * Tests the feed listing page for an user who may only update feeds.
   */
  public function testUiWithOnlyUpdatePermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add two feeds.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Login as an user who may only update the first feed.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'update ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the first feed listed with the expected links.
    $session->pageTextContains('My feed 1');
    $session->linkExists('Edit');
    $session->linkByHrefExists('/feed/1/edit');
    $session->linkNotExists('My feed 1');

    // Assert that the second feed is *not* listed.
    $session->pageTextNotContains('My feed 2');
    $session->linkByHrefNotExists('/feed/2');
    $session->linkByHrefNotExists('/feed/2/edit');

    // Assert that the following links do *not* exist for both feeds.
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
  }

  /**
   * Tests the feed listing page for an user who may only import feeds.
   */
  public function testUiWithOnlyImportPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add two feeds.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Login as an user who may only import the first feed.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'import ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the first feed listed with the expected links.
    $session->pageTextContains('My feed 1');
    $session->linkExists('Import');
    $session->linkByHrefExists('/feed/1/import');
    $session->linkNotExists('My feed 1');

    // Assert that the second feed is *not* listed.
    $session->pageTextNotContains('My feed 2');
    $session->linkByHrefNotExists('/feed/2');
    $session->linkByHrefNotExists('/feed/2/import');

    // Assert that the following links do *not* exist for both feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
  }

  /**
   * Tests the feed listing page for an user who may only schedule imports.
   */
  public function testUiWithOnlyScheduleImportPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add two feeds.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Login as an user who may only schedule the import of the first feed.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'schedule_import ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();
    // Assert that the empty text message is not shown.
    $session->pageTextNotContains('There are no feed entities yet.');

    // Assert the first feed listed with the expected links.
    $session->pageTextContains('My feed 1');
    $session->linkExists('Import in background');
    $session->linkByHrefExists('/feed/1/schedule-import');

    // Assert that the second feed is *not* listed.
    $session->pageTextNotContains('My feed 2');
    $session->linkByHrefNotExists('/feed/2');
    $session->linkByHrefNotExists('/feed/2/schedule-import');

    // Assert that the following links do *not* exist for both feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
  }

  /**
   * Tests the feed listing page for an user who may only clear feeds.
   */
  public function testUiWithOnlyClearPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add two feeds.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);

    // Login as an user who may only clear the first feed.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'clear ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the first feed listed with the expected links.
    $session->pageTextContains('My feed 1');
    $session->linkExists('Delete items');
    $session->linkByHrefExists('/feed/1/delete-items');
    $session->linkNotExists('My feed 1');

    // Assert that the second feed is *not* listed.
    $session->pageTextNotContains('My feed 2');
    $session->linkByHrefNotExists('/feed/2');
    $session->linkByHrefNotExists('/feed/2/delete-items');

    // Assert that the following links do *not* exist for both feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
  }

  /**
   * Asserts that there are no warnings nor errors displayed.
   */
  protected function assertNoWarnings() {
    $this->assertSession()->elementNotExists('css', '.messages--warning');
    $this->assertSession()->elementNotExists('css', '.messages--error');
    $this->assertSession()->pageTextNotContains('The website encountered an unexpected error.');
  }

}
