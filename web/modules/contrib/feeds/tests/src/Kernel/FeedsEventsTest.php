<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds_test_events\EventSubscriber\FeedsSubscriber;
use Drupal\node\Entity\Node;

/**
 * Tests for dispatching feeds events.
 *
 * @group feeds
 */
class FeedsEventsTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'feeds_test_events',
  ];

  /**
   * Checks the order of event dispatching messages.
   *
   * Module feeds_test_events implements all feeds events and stores a message
   * for each in $GLOBALS['feeds_test_events'].
   *
   * @param array $messages
   *   An array of plain-text messages in the order they should appear.
   */
  protected function assertEventSubscriberMessageOrder(array $messages) {
    $positions = [];
    foreach ($messages as $message) {
      // Verify that each message is found and record its position.
      $position = array_search($message, $GLOBALS['feeds_test_events']);
      if ($this->assertTrue($position !== FALSE, $message)) {
        $positions[] = $position;
      }
    }

    // Sort the positions and ensure they remain in the same order.
    $sorted = $positions;
    sort($sorted);
    $this->assertEquals($positions, $sorted, 'The event subscriber messages appear in the correct order.');
  }

  /**
   * Ensure that the prevalidate event is dispatched at the right moment.
   */
  public function testPrevalidateEvent() {
    // Create a feed type. Do not map to 'title'.
    $feed_type = $this->createFeedTypeForCsv(['guid' => 'guid'], [
      'id' => 'my_feed_type',
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
          'unique' => ['guid' => TRUE],
        ],
      ],
    ]);

    // Try to import a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Ensure that the import failed because of validation errors.
    $messages = \Drupal::messenger()->all();
    $this->assertStringContainsString('This value should not be null.', (string) $messages['warning'][0]);
    $this->assertNodeCount(0);

    // Clear messages.
    \Drupal::messenger()->deleteAll();

    // Now create a feed type with the same settings. This time, ensure that
    // \Drupal\feeds_test_events\EventSubscriber::prevalidate() sets a title on
    // the entity, which it does only for the feed type 'no_title'.
    $feed_type = $this->createFeedTypeForCsv(['guid' => 'guid'], [
      'id' => 'no_title',
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
          'unique' => ['guid' => TRUE],
        ],
      ],
    ]);

    // Try to import a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that there are no warnings this time.
    $messages = \Drupal::messenger()->all();
    $this->assertArrayNotHasKey('warning', $messages);
    // Assert that 2 nodes were created.
    $this->assertNodeCount(2);

    // Check title of the first created node.
    $node = Node::load(1);
    $this->assertEquals('foo', $node->getTitle());
  }

  /**
   * Tests skip import on presave feature.
   */
  public function testSkipImportOnPresave() {
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ], [
      'id' => 'import_skip',
    ]);

    // Import feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that only the second item was imported.
    $this->assertNodeCount(1);
    $node = Node::load(1);
    $this->assertEquals('Ut wisi enim ad minim veniam', $node->getTitle());
  }

  /**
   * Tests the order in which events are dispatched on an import.
   */
  public function testEventDispatchOrderOnImport() {
    $GLOBALS['feeds_test_events'] = [];

    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);

    // Import feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    $this->assertEventSubscriberMessageOrder([
      // Import starts with fetching.
      FeedsSubscriber::class . '::onInitImport(fetch) called',
      FeedsSubscriber::class . '::preFetch called',
      FeedsSubscriber::class . '::postFetch called',
      // Second stage is parsing.
      FeedsSubscriber::class . '::onInitImport(parse) called',
      FeedsSubscriber::class . '::preParse called',
      FeedsSubscriber::class . '::postParse called',
      // Third stage is processing, process events occur per item.
      FeedsSubscriber::class . '::onInitImport(process) called',
      FeedsSubscriber::class . '::preProcess called',
      FeedsSubscriber::class . '::prevalidate called',
      FeedsSubscriber::class . '::preSave called',
      FeedsSubscriber::class . '::postSave called',
      FeedsSubscriber::class . '::postProcess called',
      // Second item being processed.
      FeedsSubscriber::class . '::onInitImport(process) called',
      FeedsSubscriber::class . '::preProcess called',
      FeedsSubscriber::class . '::prevalidate called',
      FeedsSubscriber::class . '::preSave called',
      FeedsSubscriber::class . '::postSave called',
      FeedsSubscriber::class . '::postProcess called',
      // There are no items to clean, so the clean stage is completely skipped.
      FeedsSubscriber::class . '::onFinish called',
    ]);
  }

  /**
   * Tests the order in which events are dispatched on an expire.
   */
  public function testEventDispatchOrderOnExpire() {
    // Import items first.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ], [
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
        'expire' => 3600,
      ],
    ]);
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Set imported time of all imported items to a timestamp in the past so
    // that they expire.
    for ($i = 1; $i <= 2; $i++) {
      $node = Node::load($i);
      $node->get('feeds_item')->getItemByFeed($feed)->imported = \Drupal::service('datetime.time')->getRequestTime() - 3601;
      $node->save();
    }

    // Now expire items.
    $GLOBALS['feeds_test_events'] = [];
    $feed->startBatchExpire();
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();

    $this->assertEventSubscriberMessageOrder([
      FeedsSubscriber::class . '::onInitExpire() called',
      FeedsSubscriber::class . '::onExpire called',
      FeedsSubscriber::class . '::onInitExpire() called',
      FeedsSubscriber::class . '::onExpire called',
    ]);
  }

  /**
   * Tests the order in which events are dispatched when clearing items.
   */
  public function testEventDispatchOrderOnClear() {
    // Import items first.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Now delete all items using a batch.
    $GLOBALS['feeds_test_events'] = [];
    $feed->startBatchClear();
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();

    $this->assertEventSubscriberMessageOrder([
      FeedsSubscriber::class . '::onInitClear() called',
      FeedsSubscriber::class . '::onClear called',
    ]);
  }

}
