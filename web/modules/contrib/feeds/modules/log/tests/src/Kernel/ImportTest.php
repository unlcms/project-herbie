<?php

namespace Drupal\Tests\feeds_log\Kernel;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds_log\Entity\ImportLog;

/**
 * Tests log entries created during an import.
 *
 * @group feeds_log
 */
class ImportTest extends FeedsLogKernelTestBase {

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
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
      ],
    ]);
  }

  /**
   * Returns default feeds log settings on the feed type.
   *
   * @return array
   *   The default Feeds log settings on the feed type.
   */
  protected function getDefaultFeedsLogThirdPartySettings(): array {
    return [
      'status' => TRUE,
      'operations' => [
        'created' => 'created',
        'updated' => 'updated',
        'deleted' => 'deleted',
        'skipped' => 'skipped',
        'cleaned' => 'cleaned',
      ],
      'items' => [
        'created' => 'created',
        'updated' => 'updated',
        'deleted' => 'deleted',
        'skipped' => 'skipped',
      ],
      'source' => TRUE,
    ];
  }

  /**
   * Sets the third party settings on the feed type.
   *
   * @param array $settings
   *   The settings to save on the feed type.
   */
  protected function setFeedsLogThirdPartySettings(array $settings) {
    foreach ($settings as $key => $setting) {
      $this->feedType->setThirdPartySetting('feeds_log', $key, $setting);
    }
    $this->feedType->save();
  }

  /**
   * Tests that log entries are created for each imported item.
   */
  public function testCreated() {
    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    $this->assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals(0, $import_log->uid->target_id);
    $this->assertEquals('public://feeds/log/1/source/googlenewstz.rss2', $import_log->sources->value);

    // Assert that the source was logged with the expected contents.
    $this->assertFileIsReadable('public://feeds/log/1/source/googlenewstz.rss2');
    $this->assertFileEquals($this->resourcesPath() . '/rss/googlenewstz.rss2', 'public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that 6 log entries have been created.
    $entries = $this->getLogEntries();
    $this->assertCount(6, $entries);

    // Expected for each entry.
    $expected_defaults = [
      'import_id' => 1,
      'feed_id' => 1,
      'entity_type_id' => 'node',
      'operation' => 'created',
      'message' => '',
      'variables' => 'a:0:{}',
    ];

    // Define the expected values for each log entry that was created.
    $expected = [
      1 => [
        'lid' => 1,
        'entity_id' => 1,
        'entity_label' => 'label: First thoughts: Dems\' Black Tuesday - msnbc.com',
        'item' => 'public://feeds/log/1/items/1.json',
        'item_id' => 'guid:tag:news.google.com,2005:cluster=17593687403189',
      ] + $expected_defaults,
      2 => [
        'lid' => 2,
        'entity_id' => 2,
        'entity_label' => 'label: Obama wants to fast track a final health care bill - USA Today',
        'item' => 'public://feeds/log/1/items/2.json',
        'item_id' => 'guid:tag:news.google.com,2005:cluster=17593688083752',
      ] + $expected_defaults,
      3 => [
        'lid' => 3,
        'entity_id' => 3,
        'entity_label' => 'label: Why the Nexus One Makes Other Android Phones Obsolete - PC World',
        'item' => 'public://feeds/log/1/items/3.json',
        'item_id' => 'guid:tag:news.google.com,2005:cluster=17593685844960',
      ] + $expected_defaults,
      4 => [
        'lid' => 4,
        'entity_id' => 4,
        'entity_label' => 'label: NEWSMAKER-New Japan finance minister a fiery battler - Reuters',
        'item' => 'public://feeds/log/1/items/4.json',
        'item_id' => 'guid:tag:news.google.com,2005:cluster=17593685670703',
      ] + $expected_defaults,
      5 => [
        'lid' => 5,
        'entity_id' => 5,
        'entity_label' => 'label: Yemen Detains Al-Qaeda Suspects After Embassy Threats - Bloomberg',
        'item' => 'public://feeds/log/1/items/5.json',
        'item_id' => 'guid:tag:news.google.com,2005:cluster=17593688042489',
      ] + $expected_defaults,
      6 => [
        'lid' => 6,
        'entity_id' => 6,
        'entity_label' => 'label: Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters',
        'item' => 'public://feeds/log/1/items/6.json',
        'item_id' => 'guid:tag:news.google.com,2005:cluster=17593688298004',
      ] + $expected_defaults,
    ];

    // Assert the values of each entry.
    foreach ($expected as $index => $values) {
      foreach ($values as $key => $value) {
        $this->assertEquals($value, $entries[$index]->{$key});
      }
    }

    // Assert that logged items are readable.
    $this->assertFileIsReadable('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/5.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/6.json');

    // Assert the contents of the first logged item.
    $this->assertStringContainsString('"title":"First thoughts: Dems\' Black Tuesday - msnbc.com"', file_get_contents('public://feeds/log/1/items/1.json'));
  }

  /**
   * Tests that no logs are created for created entities.
   */
  public function testCreatedDisabled() {
    // Configure to skip logging of created entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['operations']['created']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that no logs were created.
    $entries = $this->getLogEntries();
    $this->assertCount(0, $entries);

    // Assert that no items were logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/2.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/3.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/4.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/5.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/6.json');
  }

  /**
   * Tests that no items are logged for created entities.
   */
  public function testCreatedItemDisabled() {
    // Configure to skip logging the item of created entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['items']['created']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that 6 log entries have been created.
    $entries = $this->getLogEntries();
    $this->assertCount(6, $entries);

    // Assert that no items were logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/2.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/3.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/4.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/5.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/6.json');
  }

  /**
   * Tests that log entries are created for updated items.
   */
  public function testUpdated() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);

    // Create the node to update.
    $node = $this->createNodeWithFeedsItem($feed);
    $node->feeds_item->guid = 'tag:news.google.com,2005:cluster=17593687403189';
    $node->save();

    // And import feed.
    $feed->import();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals(0, $import_log->uid->target_id);
    $this->assertEquals('public://feeds/log/1/source/googlenewstz.rss2', $import_log->sources->value);

    // Assert that the source was logged with the expected contents.
    $this->assertFileIsReadable('public://feeds/log/1/source/googlenewstz.rss2');
    $this->assertFileEquals($this->resourcesPath() . '/rss/googlenewstz.rss2', 'public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that 6 log entries have been created.
    $entries = $this->getLogEntries();
    $this->assertCount(6, $entries);

    // Check the values for the first log entry.
    $expected = [
      'lid' => 1,
      'import_id' => 1,
      'feed_id' => 1,
      'entity_type_id' => 'node',
      'operation' => 'updated',
      'message' => '',
      'variables' => 'a:0:{}',
      'entity_id' => 1,
      'entity_label' => 'label: First thoughts: Dems\' Black Tuesday - msnbc.com',
      'item' => 'public://feeds/log/1/items/1.json',
      'item_id' => 'guid:tag:news.google.com,2005:cluster=17593687403189',
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $entries[1]->{$key});
    }
  }

  /**
   * Tests that no logs are created for updated entities.
   */
  public function testUpdatedDisabled() {
    // Configure to skip logging of updated entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['operations']['updated']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);

    // Create the node to update.
    $node = $this->createNodeWithFeedsItem($feed);
    $node->feeds_item->guid = 'tag:news.google.com,2005:cluster=17593687403189';
    $node->save();

    // And import feed.
    $feed->import();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that five logs were created.
    $entries = $this->getLogEntries();
    $this->assertCount(5, $entries);

    // Assert that only five items were logged.
    $this->assertFileIsReadable('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/5.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/6.json');
  }

  /**
   * Tests that no items are logged for updated entities.
   */
  public function testUpdatedItemDisabled() {
    // Configure to skip logging the item of updated entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['items']['updated']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);

    // Create the node to update.
    $node = $this->createNodeWithFeedsItem($feed);
    $node->feeds_item->guid = 'tag:news.google.com,2005:cluster=17593687403189';
    $node->save();

    // And import feed.
    $feed->import();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that 6 logs were created.
    $entries = $this->getLogEntries();
    $this->assertCount(6, $entries);

    // Assert that only five items were logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/5.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/6.json');
  }

  /**
   * Tests that log entries are created for items that did not change.
   */
  public function testUnchanged() {
    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Import a second time.
    $feed->import();

    // Assert that 6 nodes in total have been created.
    $this->assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Assert that an import log entity was created for the second import.
    $import_log = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals(0, $import_log->uid->target_id);
    $this->assertEquals('public://feeds/log/2/source/googlenewstz.rss2', $import_log->sources->value);

    // Assert that the source was logged with the expected contents.
    $this->assertFileIsReadable('public://feeds/log/2/source/googlenewstz.rss2');
    $this->assertFileEquals($this->resourcesPath() . '/rss/googlenewstz.rss2', 'public://feeds/log/2/source/googlenewstz.rss2');

    // Assert that 6 log entries have been created for the second import.
    $entries = $this->getLogEntries(2);
    $this->assertCount(6, $entries);

    // Check the values for the first log entry.
    $expected = [
      'lid' => 7,
      'import_id' => 2,
      'feed_id' => 1,
      'entity_type_id' => 'node',
      'operation' => 'skipped',
      'message' => 'Skipped because the source data has not changed.',
      'variables' => 'a:0:{}',
      'entity_id' => 1,
      'entity_label' => 'label: First thoughts: Dems\' Black Tuesday - msnbc.com',
      'item' => 'public://feeds/log/2/items/7.json',
      'item_id' => 'guid:tag:news.google.com,2005:cluster=17593687403189',
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $entries[7]->{$key});
    }
  }

  /**
   * Tests that no logs are created for skipped entities.
   */
  public function testUnchangedDisabled() {
    // Configure to skip logging of skipped entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['operations']['skipped']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Import a second time.
    $feed->import();

    // Assert that an import log entity was created for the second import.
    $import_log = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/2/source/googlenewstz.rss2');

    // Assert that 6 log entries were created.
    $entries = $this->getLogEntries();
    $this->assertCount(6, $entries);

    // Assert that no log entries were created for the second import.
    $entries = $this->getLogEntries(2);
    $this->assertCount(0, $entries);

    // Assert that only for the first import items were logged.
    $this->assertFileIsReadable('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/5.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/6.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/7.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/8.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/9.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/10.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/11.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/12.json');
  }

  /**
   * Tests that no items are logged for skipped entities.
   */
  public function testUnchangedItemDisabled() {
    // Configure to skip logging the item of skipped entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['items']['skipped']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Import a second time.
    $feed->import();

    // Assert that an import log entity was created for the second import.
    $import_log = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/2/source/googlenewstz.rss2');

    // Assert that 6 log entries were created.
    $entries = $this->getLogEntries();
    $this->assertCount(12, $entries);

    // Assert that only for the first import items were logged.
    $this->assertFileIsReadable('public://feeds/log/1/items/1.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/2.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/3.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/4.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/5.json');
    $this->assertFileIsReadable('public://feeds/log/1/items/6.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/7.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/8.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/9.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/10.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/11.json');
    $this->assertFileDoesNotExist('public://feeds/log/2/items/12.json');
  }

  /**
   * Tests that log entries are created for items that got unpublished.
   */
  public function testUnpublishNonExistentItems() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    $this->assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    $import_log = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals(0, $import_log->uid->target_id);
    $this->assertEquals('public://feeds/log/2/source/googlenewstz_missing.rss2', $import_log->sources->value);

    // Assert that the source was logged with the expected contents.
    $this->assertFileIsReadable('public://feeds/log/2/source/googlenewstz_missing.rss2');
    $this->assertFileEquals($this->resourcesPath() . '/rss/googlenewstz_missing.rss2', 'public://feeds/log/2/source/googlenewstz_missing.rss2');

    // Assert that 6 log entries have been created for the second import.
    $entries = $this->getLogEntries(2);
    $this->assertCount(6, $entries);

    // Check the values for the item that was unpublished.
    $expected = [
      'lid' => 12,
      'import_id' => 2,
      'feed_id' => 1,
      'entity_type_id' => 'node',
      'operation' => 'cleaned',
      'message' => 'Applied action @action because the item was no longer in the source.',
      'variables' => serialize(['@action' => 'Unpublish content item']),
      'entity_id' => 6,
      'entity_label' => 'label: Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters',
      'item' => '',
      'item_id' => '',
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $entries[12]->{$key});
    }
  }

  /**
   * Tests that no logs are created for cleaned entities.
   */
  public function testUnpublishNonExistentItemsDisabled() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'entity:unpublish_action:node';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Configure to skip logging the item of cleaned entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    unset($settings['operations']['cleaned']);
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    // Assert that an import log entity was created for the second import.
    $import_log = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that a source was logged.
    $this->assertFileIsReadable('public://feeds/log/2/source/googlenewstz_missing.rss2');

    // Assert that 11 log entries have been created in total.
    $entries = $this->getLogEntries();
    $this->assertCount(11, $entries);
  }

  /**
   * Tests that log entries are created for items that got deleted.
   */
  public function testDeleteNonExistentItems() {
    // Set 'update_non_existent' setting to 'delete'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    $this->assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $feed->import();

    $import_log = ImportLog::load(2);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);
    $this->assertEquals(0, $import_log->uid->target_id);
    $this->assertEquals('public://feeds/log/2/source/googlenewstz_missing.rss2', $import_log->sources->value);

    // Assert that the source was logged with the expected contents.
    $this->assertFileIsReadable('public://feeds/log/2/source/googlenewstz_missing.rss2');
    $this->assertFileEquals($this->resourcesPath() . '/rss/googlenewstz_missing.rss2', 'public://feeds/log/2/source/googlenewstz_missing.rss2');

    // Assert that 6 log entries have been created for the second import.
    $entries = $this->getLogEntries(2);
    $this->assertCount(6, $entries);

    // Check the values for the item that was unpublished.
    $expected = [
      'lid' => 12,
      'import_id' => 2,
      'feed_id' => 1,
      'entity_type_id' => 'node',
      'operation' => 'cleaned',
      'message' => 'Deleted because the item was no longer in the source.',
      'variables' => 'a:0:{}',
      'entity_id' => 6,
      'entity_label' => 'label: Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters',
      'item' => '',
      'item_id' => '',
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $entries[12]->{$key});
    }
  }

  /**
   * Tests that logging can get completely disabled for a feed type.
   */
  public function testDisableLogging() {
    // Configure to skip logging completely.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    $settings['status'] = FALSE;
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that the import was not logged.
    $this->assertNull(ImportLog::load(1));

    // Assert that no source was logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that no log entries were created.
    $entries = $this->getLogEntries();
    $this->assertCount(0, $entries);

    // Assert that no items were logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/2.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/3.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/4.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/5.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/6.json');
  }

  /**
   * Tests that logging can get disabled for a single feed.
   */
  public function testDisableLoggingForSingleFeed() {
    // Create a feed, disable logging and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => FALSE,
    ]);
    $feed->import();

    // Assert that the import was not logged.
    $this->assertNull(ImportLog::load(1));

    // Assert that no source was logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/source/googlenewstz.rss2');

    // Assert that no log entries were created.
    $entries = $this->getLogEntries();
    $this->assertCount(0, $entries);

    // Assert that no items were logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/items/1.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/2.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/3.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/4.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/5.json');
    $this->assertFileDoesNotExist('public://feeds/log/1/items/6.json');
  }

  /**
   * Tests that logging stops after detecting many imports in a short time.
   */
  public function testStopLoggingAfterTooManyImports() {
    // Set the threshold for stop logging to a very low amount.
    $this->config('feeds_log.settings')
      ->set('stampede', [
        'max_amount' => 3,
        'age' => 1800,
      ])
      ->save();

    // Create a feed and trigger import four times. Only 3 import logs should
    // get created.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();
    $feed->import();
    $feed->import();
    $feed->import();

    $this->assertInstanceOf(ImportLog::class, ImportLog::load(1));
    $this->assertInstanceOf(ImportLog::class, ImportLog::load(1));
    $this->assertInstanceOf(ImportLog::class, ImportLog::load(1));
    $this->assertNull(ImportLog::load(4));

    // Assert that logging is now disabled for the feed.
    $feed = $this->reloadEntity($feed);
    $this->assertEquals(0, $feed->feeds_log->value);
  }

  /**
   * Tests that the source does not get logged when that is disabled.
   */
  public function testDoNotLogSource() {
    // Configure to skip logging of created entities.
    $settings = $this->getDefaultFeedsLogThirdPartySettings();
    $settings['source'] = FALSE;
    $this->setFeedsLogThirdPartySettings($settings);

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
      'feeds_log' => TRUE,
    ]);
    $feed->import();

    // Assert that an import log entity was created.
    $import_log = ImportLog::load(1);
    $this->assertEquals($feed->id(), $import_log->feed->target_id);

    // Assert that no source was logged.
    $this->assertFileDoesNotExist('public://feeds/log/1/source/googlenewstz.rss2');
  }

}
