<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\Entity\FeedType;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;

/**
 * Ensures that feed type functions work correctly.
 *
 * @group feeds
 */
class FeedTypeTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'block',
  ];

  /**
   * Tests creating a new feed type in the UI.
   */
  public function testFeedTypeCreation() {
    // Go to the drupal add feed type form.
    $this->drupalGet('admin/structure/feeds/add');

    // Check if we got to the add feed type page.
    $this->assertSession()->pageTextContains('Add feed type');

    // Create a new feed type.
    $edit = [
      'label' => 'Test Feed Type',
      'id' => 'test_feed_type',
      'description' => 'A test for feed type creation.',
      'help' => 'A help text for this feed type.',
      'fetcher' => 'http',
      'fetcher_configuration[auto_detect_feeds]' => TRUE,
      'processor_configuration[insert_new]' => ProcessorInterface::INSERT_NEW,
      'processor_configuration[update_existing]' => ProcessorInterface::UPDATE_EXISTING,
      'processor_configuration[expire]' => 3600,
      'processor_configuration[owner_id]' => 'admin (1)',
      'processor_configuration[authorize]' => FALSE,
    ];
    $this->submitForm($edit, 'Save and add mappings');

    // Assert that save message.
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Assert that settings are saved.
    $feed_type = FeedType::load('test_feed_type');
    $this->assertEquals('Test Feed Type', $feed_type->label());
    $this->assertEquals('A test for feed type creation.', $feed_type->getDescription());
    $this->assertEquals('A help text for this feed type.', $feed_type->getHelp());

    // Assert that fetcher settings are saved.
    $fetcher = $feed_type->getFetcher()->getConfiguration();
    $this->assertTrue($fetcher['auto_detect_feeds']);

    // Assert that processor settings are saved.
    $processor = $feed_type->getProcessor()->getConfiguration();
    // Assert that the loaded test feed type processor content type is article.
    $this->assertEquals('article', $processor['values']['type']);
    // Assert that 'insert new content items' is selected.
    $this->assertEquals(ProcessorInterface::INSERT_NEW, $processor['insert_new']);
    // Assert that 'update existing content' is selected.
    $this->assertEquals(ProcessorInterface::UPDATE_EXISTING, $processor['update_existing']);
    // Assert that the expiration is set to 'Every 1 hour' value.
    $this->assertEquals(3600, $processor['expire']);
    // Assert that the loaded test feed type's owner is admin (id = 1).
    $this->assertEquals('1', $processor['owner_id']);
    // Assert that authorize checkbox is unselected.
    $this->assertFalse($processor['authorize']);
  }

  /**
   * Tests editing a feed type using the UI.
   */
  public function testEditFeedType() {
    // Creates a new feed type for further editions.
    $feed_type = $this->createFeedType([
      'label' => 'Test Feed Type',
      'id' => 'test_feed_type',
      'description' => 'A test for feed type creation.',
      'help' => 'A help text for this feed type.',
      'fetcher' => 'http',
      'fetcher_configuration[auto_detect_feeds]' => TRUE,
      'processor_configuration[insert_new]' => ProcessorInterface::INSERT_NEW,
      'processor_configuration[update_existing]' => ProcessorInterface::UPDATE_EXISTING,
      'processor_configuration[expire]' => 3600,
      'processor_configuration[authorize]' => FALSE,
    ]);

    // Go to the feed types list.
    $this->drupalGet('admin/structure/feeds');

    // Assert that the created feed type exists in the feed types list.
    $this->assertSession()->pageTextContains('Test Feed Type');

    // Go to the created feed type edition page.
    $this->drupalGet('admin/structure/feeds/manage/test_feed_type');

    // Checks that we're on the feed type edition page.
    $this->assertSession()->pageTextContains('Edit Test Feed Type');

    // Make some changes to our created feed type and saves it.
    $edit = [
      'label' => 'Edited Feed Type',
      'description' => 'An edited feed type.',
      'help' => 'A help text for the edited feed type.',
      'fetcher_configuration[auto_detect_feeds]' => FALSE,
      'processor_configuration[insert_new]' => ProcessorInterface::SKIP_NEW,
      'processor_configuration[update_existing]' => ProcessorInterface::SKIP_EXISTING,
      'processor_configuration[expire]' => 10800,
      'processor_configuration[owner_id]' => 'admin (1)',
      'processor_configuration[authorize]' => TRUE,
    ];
    $this->submitForm($edit, 'Save feed type');

    // Check if changes were saved.
    $this->assertSession()->pageTextContains('Your changes have been saved.');

    // Reload the updated feed type.
    $feed_type = $this->reloadEntity($feed_type);

    // Assert that settings are updated.
    $this->assertEquals('Edited Feed Type', $feed_type->label());
    $this->assertEquals('An edited feed type.', $feed_type->getDescription());
    $this->assertEquals('A help text for the edited feed type.', $feed_type->getHelp());

    // Assert that fetcher settings are updated.
    $fetcher = $feed_type->getFetcher()->getConfiguration();
    $this->assertFalse($fetcher['auto_detect_feeds']);

    // Assert that processor settings are updated.
    $processor = $feed_type->getProcessor()->getConfiguration();
    // Assert that the loaded test feed type processor content type is article.
    $this->assertEquals('article', $processor['values']['type']);
    // Assert that 'Do not insert new content items' is selected.
    $this->assertEquals(ProcessorInterface::SKIP_NEW, $processor['insert_new']);
    // Assert that 'Do not update existing content items' is selected.
    $this->assertEquals(ProcessorInterface::SKIP_EXISTING, $processor['update_existing']);
    // Assert that the expiration is set to 'Every 3 hours' value.
    $this->assertEquals(10800, $processor['expire']);
    // Assert that the loaded test feed type's owner is admin (id = 1).
    $this->assertEquals('1', $processor['owner_id']);
    // Assert that authorize checkbox is unselected.
    $this->assertTrue($processor['authorize']);
  }

  /**
   * Tests deleting a feed type that has content.
   */
  public function testFeedTypeWithContentDeletion() {
    $this->drupalPlaceBlock('page_title_block');

    // Create a feed type programmatically.
    $feed_type = $this->createFeedType();
    $feed_type_label = $feed_type->label();
    // Add a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Attempt to delete the feed type, which should not be allowed.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/delete');
    $this->assertSession()->pageTextContains("$feed_type_label is used by 1 feed on your site. You can not remove this feed type until you have removed all of the $feed_type_label feeds.");
    $this->assertSession()->pageTextNotContains('This action cannot be undone.');

    // Delete the feed.
    $feed->delete();
    // Attempt to delete the feed type, which should now be allowed.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the feed type $feed_type_label?");
    $this->assertSession()->pageTextContains('This action cannot be undone.');

    // Confirm deletion.
    $this->submitForm([], 'Delete');
    $this->assertNull($this->reloadEntity($feed_type), 'The feed type is deleted.');
    $this->assertSession()->pageTextContains("The feed type $feed_type_label has been deleted.");
  }

}
