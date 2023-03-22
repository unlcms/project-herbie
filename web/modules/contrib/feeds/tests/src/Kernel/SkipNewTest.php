<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\StateInterface;
use Drupal\node\Entity\Node;

/**
 * Tests the feature of creating/skipping new items.
 *
 * @group feeds
 */
class SkipNewTest extends FeedsKernelTestBase {

  /**
   * The process state after an import.
   *
   * @var \Drupal\feeds\StateInterface
   */
  protected $processState;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup an event dispatcher. We use this to check the number of created and
    // updated items after an import.
    $this->container->get('event_dispatcher')->addListener(FeedsEvents::IMPORT_FINISHED, [
      $this,
      'importFinished',
    ]);

    // Add body field.
    $this->setUpBodyField();
  }

  /**
   * Event callback for the 'feeds.import_finished' event.
   *
   * Sets the processState property, so that the tests can read this.
   *
   * @param \Drupal\feeds\Event\ImportFinishedEvent $event
   *   The Feeds event that was dispatched.
   */
  public function importFinished(ImportFinishedEvent $event) {
    $this->processState = $event->getFeed()->getState(StateInterface::PROCESS);
  }

  /**
   * Creates a feed type used by several tests in this class.
   *
   * @param array $processor_configuration
   *   (optional) The processor configuration.
   */
  protected function createFeedTypeForThisTest(array $processor_configuration = []) {
    // Create a feed type and set the title as unique target.
    return $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'rss2',
      ],
      'processor_configuration' => $processor_configuration + [
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
          'settings' => [
            'language' => NULL,
          ],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'description'],
          'settings' => [
            'format' => 'plain_text',
            'language' => NULL,
          ],
        ],
      ],
    ]);
  }

  /**
   * Tests skip new items when there are no nodes yet.
   */
  public function testSkipNewItems() {
    // Configure that new items should not be imported.
    $feed_type = $this->createFeedTypeForThisTest([
      'insert_new' => ProcessorInterface::SKIP_NEW,
    ]);

    // Create a feed and import.
    // No nodes should be created, as all are new.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $feed->import();

    // Assert that no nodes were created.
    $this->assertNodeCount(0);
    $this->assertEquals(0, $this->processState->created);
    // All items should have been skipped.
    $this->assertEquals(25, $this->processState->skipped);
  }

  /**
   * Tests skip new items without update existing as well.
   */
  public function testSkipNewAndSkipExisting() {
    // Configure that new items should not be imported and that existing items
    // should not be updated.
    $feed_type = $this->createFeedTypeForThisTest([
      'insert_new' => ProcessorInterface::SKIP_NEW,
      'update_existing' => ProcessorInterface::SKIP_EXISTING,
    ]);

    // Create two nodes whose title is in the feed.
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Dries Buytaert: Eén using Drupal',
      'body' => 'Foo',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => 'NodeOne: The new Feeds module',
      'body' => 'Feeds exists for more than a decade now.',
    ]);
    $node2->save();

    // Create a feed and import.
    // No nodes should be created nor updated.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $feed->import();

    // Assert no created nodes and two nodes in total.
    $this->assertEquals(0, $feed->getItemCount());
    $this->assertNodeCount(2);
    $this->assertEquals(0, $this->processState->created);
    $this->assertEquals(0, $this->processState->updated);
    // All items should have been skipped.
    $this->assertEquals(25, $this->processState->skipped);

    // Assert that the existing nodes did not change and were not touched by
    // Feeds.
    $node1 = $this->reloadEntity($node1);
    $this->assertEquals('Foo', $node1->body->value);
    $this->assertEmpty($node1->feeds_item);
    $node2 = $this->reloadEntity($node2);
    $this->assertEquals('Feeds exists for more than a decade now.', $node2->body->value);
    $this->assertEmpty($node2->feeds_item);
  }

  /**
   * Tests skip new items with update existing.
   */
  public function testSkipNewAndUpdateExisting() {
    // Configure that new items should not be imported and that existing items
    // may be updated.
    $feed_type = $this->createFeedTypeForThisTest([
      'insert_new' => ProcessorInterface::SKIP_NEW,
      'update_existing' => ProcessorInterface::UPDATE_EXISTING,
    ]);

    // Create two nodes whose title is in the feed.
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Dries Buytaert: Eén using Drupal',
      'body' => 'Foo',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => 'NodeOne: The new Feeds module',
      'body' => 'Feeds exists for more than a decade now.',
    ]);
    $node2->save();

    // Create a feed and import.
    // No nodes should be created nor updated.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $feed->import();

    // Two nodes should be updated, but no items should get created.
    $this->assertEquals(2, $feed->getItemCount());
    $this->assertNodeCount(2);
    $this->assertEquals(0, $this->processState->created);
    $this->assertEquals(2, $this->processState->updated);

    // Assert that the existing nodes changed.
    $node1 = $this->reloadEntity($node1);
    $this->assertStringContainsString('a public TV station reaching millions of people in Belgium', $node1->body->value);
    $this->assertNotEmpty($node1->get('feeds_item')->getItemByFeed($feed)->imported);
    $node2 = $this->reloadEntity($node2);
    $this->assertStringContainsString('FeedAPI has for long been the mainstream solution for this kind of problems.', $node2->body->value);
    $this->assertNotEmpty($node2->get('feeds_item')->getItemByFeed($feed)->imported);

    // Change "insert_new" setting to insert new items to verify if changing the
    // setting later has the effect that new items will be imported as yet.
    $config = $feed_type->getProcessor()->getConfiguration();
    $config['insert_new'] = ProcessorInterface::INSERT_NEW;
    $feed_type->getProcessor()->setConfiguration($config);
    $feed_type->save();

    // Import. 23 nodes should get created. No nodes should be updated, because
    // these already got updated during the previous import.
    $feed = $this->reloadEntity($feed);
    $feed->import();
    $this->assertEquals(25, $feed->getItemCount());
    $this->assertNodeCount(25);
    $this->assertEquals(23, $this->processState->created);
    $this->assertEquals(0, $this->processState->updated);
    $this->assertEquals(2, $this->processState->skipped);
  }

}
