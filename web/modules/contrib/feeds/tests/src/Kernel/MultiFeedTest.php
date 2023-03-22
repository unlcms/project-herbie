<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\StateInterface;
use Drupal\node\Entity\Node;

/**
 * Tests adding two feeds on the same entities with unlimited cardinality.
 *
 * @group feeds
 */
class MultiFeedTest extends FeedsKernelTestBase {

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

    // Set up an event dispatcher to check the number of created and/or updated
    // items after an import.
    $this->container->get('event_dispatcher')
      ->addListener(FeedsEvents::IMPORT_FINISHED, [$this, 'importFinished']);

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
   * Tests multiple feed types on the same entity.
   */
  public function testMultipleFeedTypesOnSameEntity() {
    $processor_configuration = [
      'authorize' => FALSE,
      'update_existing' => ProcessorInterface::UPDATE_EXISTING,
      'values' => [
        'type' => 'article',
      ],
    ];
    // Create a text field that will hold author as text.
    $this->createFieldWithStorage('field_another_author');

    // Create a feed type to populate the title and body.
    $feed_type1 = $this->createFeedTypeForCsv([
      'title' => 'title',
      'body' => 'body',
    ], [
      'processor_configuration' => $processor_configuration,
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'body'],
          'settings' => [
            'format' => 'plain_text',
            'language' => NULL,
          ],
        ],
      ],
    ]);
    // 1. Create and Run import with the first feed type.
    $feed1 = $this->createFeed($feed_type1->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed1->import();
    // 1.a. Assert entry counts.
    $this->assertEquals(2, $feed1->getItemCount());
    $this->assertNodeCount(2);
    $this->assertEquals(2, $this->processState->created);
    // 1.b. Re-run the import with feed of type 1, confirm nothing updated.
    $feed1->import();
    $this->assertEquals(2, $this->processState->skipped);
    // 1.c. Get one of the nodes created with feed 1 and use to assert values
    // after running feed 2.
    $node = Node::load(1);

    // Create a feed type to populate the another author field only.
    $feed_type2 = $this->createFeedTypeForCsv([
      'title' => 'title',
      'author' => 'author',
    ], [
      'processor_configuration' => $processor_configuration,
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'field_another_author',
          'map' => ['value' => 'author'],
          'settings' => ['format' => 'plain_text'],
        ],
      ],
    ]);
    // 2. Create and Run import with the second feed type.
    $feed2 = $this->createFeed($feed_type2->id(), [
      'source' => $this->resourcesPath() . '/csv/content-with-author.csv',
    ]);
    $feed2->import();
    // 2.a. Assert entry counts.
    $this->assertEquals(3, $feed2->getItemCount());
    $this->assertNodeCount(3);
    $this->assertEquals(1, $this->processState->created);
    $this->assertEquals(2, $this->processState->updated);
    // 2.b. Checks if node 1 value from feed 1 wasn't overwritten by feed 2.
    $this->assertEquals('Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.', $node->body->value);
    // 2.c. Re-run the import with feed of type 2, confirm nothing updated.
    $feed2->import();
    $this->assertEquals(3, $this->processState->skipped);
    // 1.c. Get one of the nodes created with feed 2 and use to assert values
    // after re-running feed 1 to check if it wasn't overwritten.
    $node = Node::load(2);

    // 3. Re-run the import with feed of type 1 and 2, confirm nothing updated.
    $feed1->import();
    $this->assertEquals(2, $this->processState->skipped);
    $this->assertEquals('Morticia', $node->field_another_author->value);
    $feed2->import();
    $this->assertEquals(3, $this->processState->skipped);

    // 4. Checks if the node contains both feed items.
    $expected_feed_item_target_ids = [$feed1->id(), $feed2->id()];
    $node_feed_item_target_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $node->feeds_item->getValue());
    $this->assertEquals($expected_feed_item_target_ids, $node_feed_item_target_ids);
  }

}
