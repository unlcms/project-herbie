<?php

namespace Drupal\Tests\feeds_log\Functional;

use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests for the Feeds log UI.
 *
 * @group feeds_log
 */
class UiTest extends FeedsLogBrowserTestBase {

  /**
   * Tests viewing a source.
   */
  public function testViewSource() {
    // Create a feed type.
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

    // Go to the logs page.
    $this->drupalGet('/feed/1/log');
    $this->assertSession()->linkByHrefExists('/system/files/feeds/log/1/source/content.csv');

    // And view the source.
    $this->drupalGet('/system/files/feeds/log/1/source/content.csv');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('1,"Lorem ipsum"');
  }

  /**
   * Tests viewing an item.
   */
  public function testViewItem() {
    // Create a feed type.
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

    // Go to the page to view entries.
    $this->drupalGet('/feed/1/log/1');
    $this->assertSession()->linkByHrefExists('/system/files/feeds/log/1/items/1.json');

    // And view the item.
    $this->drupalGet('/system/files/feeds/log/1/items/1.json');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('{"guid":"1","title":"Lorem ipsum"');
  }

  /**
   * Tests that logs are not accessible for users with limited privileges.
   */
  public function testNoAccessAnonymousUser() {
    // Logout and become an anonymous user.
    $this->drupalLogout();

    // Create a feed type.
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

    // Try to go the logs page.
    $this->drupalGet('/feed/1/log');
    $this->assertSession()->statusCodeEquals(403);

    // Try to view the source.
    $this->drupalGet('/system/files/feeds/log/1/source/content.csv');
    $this->assertSession()->statusCodeEquals(403);

    // Try to view a logged item.
    $this->drupalGet('/system/files/feeds/log/1/items/1.json');
    $this->assertSession()->statusCodeEquals(403);

    // Try to go to the page for clearing the logs.
    $this->drupalGet('/feed/1/log/clear');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests if a single logged import can be deleted in the UI.
   */
  public function testDeleteSingleImportLog() {
    // Create a feed type.
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

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that there is a button for deleting the logs.
    $this->drupalGet('/feed/1/log');
    $this->assertSession()->linkByHrefExists('/feed/1/log/1/delete');

    // Go to the delete page.
    $this->drupalGet('/feed/1/log/1/delete');
    // And confirm deletion.
    $this->submitForm([], 'Delete');

    // Assert that we got sent back to the feeds log overview page.
    $this->assertSession()->addressEquals('/feed/1/log');

    // Assert that the import log entity no longer exists.
    $import_log = $this->reloadEntity($import_log);
    $this->assertNull($import_log);

    // Assert that no logs are shown anymore.
    $this->assertSession()->linkByHrefNotExists('/feed/1/log/1/delete');
    $this->assertSession()->pageTextContains('There are no logged imports yet.');
  }

  /**
   * Tests if all logs for a single feed can be deleted in the UI.
   */
  public function testDeleteAllLogsSingleFeed() {
    // Create a feed type.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);

    // Import twice.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'feeds_log' => TRUE,
    ]);
    $feed->import();
    $feed->import();

    // Assert that two import log entities have been created.
    $import_log1 = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log1->feed->target_id);
    $import_log2 = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log2->feed->target_id);

    // Assert that there is a link for deleting all logs.
    $this->drupalGet('/feed/1/log');
    $this->assertSession()->linkByHrefExists('/feed/1/log/clear');

    // Go to the page to clear all logs.
    $this->drupalGet('/feed/1/log/clear');
    // And confirm deletion.
    $this->submitForm([], 'Confirm');

    // Assert that we got sent back to the feeds log overview page.
    $this->assertSession()->addressEquals('/feed/1/log');

    // Assert that the import log entities no longer exists.
    $this->assertNull($this->reloadEntity($import_log1));
    $this->assertNull($this->reloadEntity($import_log2));

    // Assert that no logs are shown anymore.
    $this->assertSession()->pageTextContains('There are no logged imports yet.');
  }

  /**
   * Tests deleting single log when feeds log view is disabled.
   */
  public function testDeleteSingleImportLogWithViewDisabled() {
    // Disable log view.
    \Drupal::entityTypeManager()->getStorage('view')
      ->load('feeds_import_logs')
      ->setStatus(FALSE)
      ->save();
    drupal_flush_all_caches();

    $this->testDeleteSingleImportLog();
  }

  /**
   * Tests deleting all logs when feeds log view is disabled.
   */
  public function testDeleteAllLogsSingleFeedWithViewDisabled() {
    // Disable log view.
    \Drupal::entityTypeManager()->getStorage('view')
      ->load('feeds_import_logs')
      ->setStatus(FALSE)
      ->save();
    drupal_flush_all_caches();

    $this->testDeleteAllLogsSingleFeed();
  }

}
