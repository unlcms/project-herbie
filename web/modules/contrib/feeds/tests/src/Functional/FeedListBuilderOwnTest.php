<?php

namespace Drupal\Tests\feeds\Functional;

/**
 * Tests the feed listing page for users that only have "own" permissions.
 *
 * @group feeds
 */
class FeedListBuilderOwnTest extends FeedsBrowserTestBase {

  /**
   * Tests the feed listing page for an user who may only view "own" feeds.
   */
  public function testUiWithOnlyOwnViewPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);

    // Login as an user who may only view own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'view own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkByHrefExists('/feed/2');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/3/edit');
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkByHrefNotExists('/feed/3/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkByHrefNotExists('/feed/3/schedule-import');
    $session->linkNotExists('Delete');
    $session->linkByHrefNotExists('/feed/1/delete');
    $session->linkByHrefNotExists('/feed/2/delete');
    $session->linkByHrefNotExists('/feed/3/delete');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
    $session->linkByHrefNotExists('/feed/3/delete-items');
    $session->linkNotExists('Unlock');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->linkByHrefNotExists('/feed/2/unlock');
    $session->linkByHrefNotExists('/feed/3/unlock');
  }

  /**
   * Tests the feed listing page for an user who may only update "own" feeds.
   */
  public function testUiWithOnlyOwnUpdatePermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);

    // Login as an user who may only update own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'update own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkExists('Edit');
    $session->linkByHrefExists('/feed/2/edit');
    $session->linkNotExists('My feed 2');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');
    $session->linkByHrefNotExists('/feed/3/edit');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkByHrefNotExists('/feed/3/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkByHrefNotExists('/feed/3/schedule-import');
    $session->linkNotExists('Delete');
    $session->linkByHrefNotExists('/feed/1/delete');
    $session->linkByHrefNotExists('/feed/2/delete');
    $session->linkByHrefNotExists('/feed/3/delete');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
    $session->linkByHrefNotExists('/feed/3/delete-items');
    $session->linkNotExists('Unlock');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->linkByHrefNotExists('/feed/2/unlock');
    $session->linkByHrefNotExists('/feed/3/unlock');
  }

  /**
   * Tests the feed listing page for an user who may only import "own" feeds.
   */
  public function testUiWithOnlyOwnImportPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);

    // Login as an user who may only import own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'import own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkExists('Import');
    $session->linkByHrefExists('/feed/2/import');
    $session->linkNotExists('My feed 2');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');
    $session->linkByHrefNotExists('/feed/3/import');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/3/edit');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkByHrefNotExists('/feed/3/schedule-import');
    $session->linkNotExists('Delete');
    $session->linkByHrefNotExists('/feed/1/delete');
    $session->linkByHrefNotExists('/feed/2/delete');
    $session->linkByHrefNotExists('/feed/3/delete');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
    $session->linkByHrefNotExists('/feed/3/delete-items');
    $session->linkNotExists('Unlock');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->linkByHrefNotExists('/feed/2/unlock');
    $session->linkByHrefNotExists('/feed/3/unlock');
  }

  /**
   * Tests the feed listing for an user who may only schedule "own" imports.
   */
  public function testUiWithOnlyScheduleOwnImportPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);

    // Login as an user who may only schedule import own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'schedule_import own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();
    // Assert that the empty text message is not shown.
    $session->pageTextNotContains('There are no feed entities yet.');

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkExists('Import in background');
    $session->linkByHrefExists('/feed/2/schedule-import');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');
    $session->linkByHrefNotExists('/feed/3/schedule-import');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/3/edit');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkByHrefNotExists('/feed/3/import');
    $session->linkNotExists('Delete');
    $session->linkByHrefNotExists('/feed/1/delete');
    $session->linkByHrefNotExists('/feed/2/delete');
    $session->linkByHrefNotExists('/feed/3/delete');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
    $session->linkByHrefNotExists('/feed/3/delete-items');
    $session->linkNotExists('Unlock');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->linkByHrefNotExists('/feed/2/unlock');
    $session->linkByHrefNotExists('/feed/3/unlock');
  }

  /**
   * Tests the feed listing page for an user who may only clear "own" feeds.
   */
  public function testUiWithOnlyOwnClearPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);

    // Login as an user who may only clear own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'clear own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkExists('Delete items');
    $session->linkByHrefExists('/feed/2/delete-items');
    $session->linkNotExists('My feed 2');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');
    $session->linkByHrefNotExists('/feed/3/delete-items');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/3/edit');
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/3/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkByHrefNotExists('/feed/3/schedule-import');
    $session->linkNotExists('Unlock');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->linkByHrefNotExists('/feed/2/unlock');
    $session->linkByHrefNotExists('/feed/3/unlock');
  }

  /**
   * Tests the feed listing page for an user who may only delete "own" feeds.
   */
  public function testUiWithOnlyOwnDeletePermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);

    // Login as an user who may only delete own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'view own ' . $feed_type_1->id() . ' feeds',
      'delete own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user.
    $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkExists('Delete');
    $session->linkByHrefExists('/feed/2/delete');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->linkByHrefNotExists('/feed/1/delete');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');
    $session->linkByHrefNotExists('/feed/3/delete');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/3/edit');
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkByHrefNotExists('/feed/3/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkByHrefNotExists('/feed/3/schedule-import');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
    $session->linkByHrefNotExists('/feed/3/delete-items');
    $session->linkNotExists('Unlock');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->linkByHrefNotExists('/feed/2/unlock');
    $session->linkByHrefNotExists('/feed/3/unlock');
  }

  /**
   * Tests the feed listing page for an user who may only unlock "own" feeds.
   */
  public function testUiWithOnlyOwnUnlockPermissions() {
    // Add two feed types.
    $feed_type_1 = $this->createFeedType();
    $feed_type_2 = $this->createFeedType();

    // Add a feed as admin.
    $feed1 = $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 1',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss1',
    ]);
    $feed1->lock();

    // Login as an user who may only unlock own feeds of 1st feed type.
    $account = $this->drupalCreateUser([
      'access feed overview',
      'view own ' . $feed_type_1->id() . ' feeds',
      'unlock own ' . $feed_type_1->id() . ' feeds',
    ]);
    $this->drupalLogin($account);

    // Add 2nd & 3rd feed of both feed types as the new user and lock them.
    $feed2 = $this->createFeed($feed_type_1->id(), [
      'title' => 'My feed 2',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
      'uid' => $account->id(),
    ]);
    $feed2->lock();
    $feed3 = $this->createFeed($feed_type_2->id(), [
      'title' => 'My feed 3',
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss3',
      'uid' => $account->id(),
    ]);
    $feed3->lock();

    // Go to the feed listing page.
    $this->drupalGet('admin/content/feed');
    $session = $this->assertSession();

    // Assert that there are no warnings nor errors displayed.
    $this->assertNoWarnings();

    // Assert the 2nd user created feed is listed with the expected links.
    $session->pageTextContains('My feed 2');
    $session->linkExists('Unlock');
    $session->linkByHrefExists('/feed/2/unlock');

    // Assert that the 1st admin and 3rd user created feeds are *not* listed.
    $session->pageTextNotContains('My feed 1');
    $session->linkByHrefNotExists('/feed/1');
    $session->linkByHrefNotExists('/feed/1/unlock');
    $session->pageTextNotContains('My feed 3');
    $session->linkByHrefNotExists('/feed/3');
    $session->linkByHrefNotExists('/feed/3/unlock');

    // Assert that the following links do *not* exist for all feeds.
    $session->linkNotExists('Edit');
    $session->linkByHrefNotExists('/feed/1/edit');
    $session->linkByHrefNotExists('/feed/2/edit');
    $session->linkByHrefNotExists('/feed/3/edit');
    $session->linkNotExists('Import');
    $session->linkByHrefNotExists('/feed/1/import');
    $session->linkByHrefNotExists('/feed/2/import');
    $session->linkByHrefNotExists('/feed/3/import');
    $session->linkNotExists('Import in background');
    $session->linkByHrefNotExists('/feed/1/schedule-import');
    $session->linkByHrefNotExists('/feed/2/schedule-import');
    $session->linkByHrefNotExists('/feed/3/schedule-import');
    $session->linkNotExists('Delete items');
    $session->linkByHrefNotExists('/feed/1/delete-items');
    $session->linkByHrefNotExists('/feed/2/delete-items');
    $session->linkByHrefNotExists('/feed/3/delete-items');
    $session->linkNotExists('Delete');
    $session->linkByHrefNotExists('/feed/1/delete');
    $session->linkByHrefNotExists('/feed/2/delete');
    $session->linkByHrefNotExists('/feed/3/delete');
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
