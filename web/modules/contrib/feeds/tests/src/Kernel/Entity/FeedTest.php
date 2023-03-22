<?php

namespace Drupal\Tests\feeds\Kernel\Entity;

use Drupal\feeds\StateInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\Feeds\State\CleanStateInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\feeds\Kernel\TestLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\Entity\Feed
 * @group feeds
 */
class FeedTest extends FeedsKernelTestBase {

  /**
   * A feed type that can be used for feed entities.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
    ]);
  }

  /**
   * @covers ::getSource
   */
  public function testGetSource() {
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => 'http://www.example.com',
    ]);

    $this->assertEquals('http://www.example.com', $feed->getSource());
  }

  /**
   * @covers ::setSource
   * @covers ::getSource
   */
  public function testSetSource() {
    $feed = $this->createFeed($this->feedType->id());
    $feed->setSource('http://www.example.com');
    $this->assertEquals('http://www.example.com', $feed->getSource());
  }

  /**
   * @covers ::getType
   */
  public function testGetType() {
    $feed = $this->createFeed($this->feedType->id());
    $feed_type = $feed->getType();
    $this->assertInstanceOf(FeedTypeInterface::class, $feed_type);
    $this->assertSame($this->feedType->id(), $feed_type->id());
  }

  /**
   * @covers ::getCreatedTime
   */
  public function testGetCreatedTime() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_int($feed->getCreatedTime()));
  }

  /**
   * @covers ::setCreatedTime
   * @covers ::getCreatedTime
   */
  public function testSetCreatedTime() {
    $feed = $this->createFeed($this->feedType->id());
    $timestamp = time();
    $feed->setCreatedTime($timestamp);
    $this->assertSame($timestamp, $feed->getCreatedTime());
  }

  /**
   * @covers ::getImportedTime
   * @covers ::getNextImportTime
   */
  public function testGetImportedTime() {
    $feed = $this->createFeed($this->feedType->id());

    // Since there is nothing imported yet, there is no import time.
    $this->assertSame(0, $feed->getImportedTime());
    // And there is also no next import time yet.
    $this->assertSame(-1, $feed->getNextImportTime());

    // Setup periodic import and import something.
    $this->feedType->set('import_period', 3600);
    $this->feedType->save();
    $feed = $this->reloadFeed($feed);
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->import();

    $this->assertGreaterThanOrEqual(\Drupal::time()->getRequestTime(), $feed->getImportedTime());
    $this->assertSame($feed->getImportedTime() + 3600, $feed->getNextImportTime());
  }

  /**
   * @covers ::startBatchImport
   */
  public function testStartBatchImport() {
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Assert that no batch was started yet.
    $this->assertEquals([], batch_get());

    // Start batch import.
    $feed->startBatchImport();

    // Assert that a single batch was initiated now.
    $batch = batch_get();
    $this->assertCount(1, $batch['sets']);
  }

  /**
   * @covers ::startCronImport
   * @covers ::getQueuedTime
   */
  public function testStartCronImport() {
    // @todo Remove installSchema() when Drupal 9.0 is no longer supported.
    // https://www.drupal.org/node/3143286
    if (version_compare(\Drupal::VERSION, '9.1', '<')) {
      // Install key/value expire schema.
      $this->installSchema('system', ['key_value_expire']);
    }

    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Assert that the item is not queued yet.
    $this->assertEquals(0, $feed->getQueuedTime());
    $queue = \Drupal::service('queue')->get('feeds_feed_refresh:' . $feed->bundle());
    $this->assertEquals(0, $queue->numberOfItems());

    $feed->startCronImport();
    $this->assertGreaterThanOrEqual(\Drupal::time()->getRequestTime(), $feed->getQueuedTime());

    // Verify that a queue item is created.
    $this->assertEquals(1, $queue->numberOfItems());
  }

  /**
   * @covers ::startCronImport
   */
  public function testStartCronImportFailsOnLockedFeed() {
    // @todo Remove installSchema() when Drupal 9.0 is no longer supported.
    // https://www.drupal.org/node/3143286
    if (version_compare(\Drupal::VERSION, '9.1', '<')) {
      // Install key/value expire schema.
      $this->installSchema('system', ['key_value_expire']);
    }

    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Lock a feed.
    $feed->lock();

    // Assert that starting a cron import task now fails.
    $this->expectException(LockException::class);
    $feed->startCronImport();
  }

  /**
   * @covers ::startBatchClear
   */
  public function testStartBatchClear() {
    // Make sure something is imported first.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that no batch was started yet.
    $this->assertEquals([], batch_get());

    // Start batch clear.
    $feed->startBatchClear();

    // Assert that a single batch was initiated now.
    $batch = batch_get();
    $this->assertCount(1, $batch['sets']);
  }

  /**
   * @covers ::pushImport
   */
  public function testPushImport() {
    $feed = $this->createFeed($this->feedType->id());
    $feed->pushImport(file_get_contents($this->resourcesPath() . '/rss/googlenewstz.rss2'));

    // pushImport() is expected to put a job on a queue. Run all items from
    // this queue.
    $this->runCompleteQueue('feeds_feed_refresh:' . $this->feedType->id());

    // Assert that 6 nodes have been created.
    $this->assertNodeCount(6);
  }

  /**
   * @covers ::startBatchExpire
   */
  public function testStartBatchExpire() {
    // Turn on 'expire' option on feed type so that there's something to expire.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['expire'] = 3600;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Make sure something is imported first.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that no batch was started yet.
    $this->assertEquals([], batch_get());

    // Start batch expire.
    $feed->startBatchExpire();

    // Assert that still no batch was created, since there was nothing to
    // expire.
    $this->assertEquals([], batch_get());

    // Now manually change the imported time of one node to be in the past.
    $node = Node::load(1);
    $node->get('feeds_item')->getItemByFeed($feed)->imported = \Drupal::time()->getRequestTime() - 3601;
    $node->save();

    // Start batch expire again and assert that there is a batch now.
    $feed->startBatchExpire();
    $batch = batch_get();
    $this->assertCount(1, $batch['sets']);
  }

  /**
   * @covers ::finishImport
   * @covers ::getImportedTime
   */
  public function testFinishImport() {
    $feed = $this->createFeed($this->feedType->id());
    $feed->finishImport();

    // Assert imported time was updated.
    $this->assertGreaterThanOrEqual(\Drupal::time()->getRequestTime(), $feed->getImportedTime());
  }

  /**
   * Tests that the event 'feeds.import_finished' gets dispatched.
   *
   * @covers ::finishImport
   */
  public function testDispatchImportFinishedEvent() {
    $dispatcher = new EventDispatcher();
    $feed = $this->getMockBuilder(Feed::class)
      ->setMethods(['eventDispatcher', 'getType'])
      ->setConstructorArgs([
        ['type' => $this->feedType->id()],
        'feeds_feed',
        $this->feedType->id(),
      ])
      ->getMock();

    $feed->expects($this->once())
      ->method('getType')
      ->willReturn($this->feedType);

    $feed->expects($this->once())
      ->method('eventDispatcher')
      ->willReturn($dispatcher);

    $dispatcher->addListener(FeedsEvents::IMPORT_FINISHED, function (ImportFinishedEvent $event) {
      throw new \Exception();
    });

    $this->expectException(\Exception::class);
    $feed->finishImport();
  }

  /**
   * @covers ::finishClear
   */
  public function testFinishClear() {
    $feed = $this->createFeed($this->feedType->id());
    $feed->finishClear();
  }

  /**
   * @covers ::progressFetching
   */
  public function testProgressFetching() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_float($feed->progressFetching()));
  }

  /**
   * @covers ::progressParsing
   */
  public function testProgressParsing() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_float($feed->progressParsing()));
  }

  /**
   * @covers ::progressImporting
   */
  public function testProgressImporting() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_float($feed->progressImporting()));
  }

  /**
   * @covers ::progressCleaning
   */
  public function testProgressCleaning() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_float($feed->progressCleaning()));
  }

  /**
   * @covers ::progressClearing
   */
  public function testProgressClearing() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_float($feed->progressClearing()));
  }

  /**
   * @covers ::progressExpiring
   */
  public function testProgressExpiring() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertTrue(is_float($feed->progressExpiring()));
  }

  /**
   * @covers ::getState
   */
  public function testGetState() {
    $feed = $this->createFeed($this->feedType->id());
    $this->assertInstanceOf(StateInterface::class, $feed->getState(StateInterface::FETCH));
    $this->assertInstanceOf(StateInterface::class, $feed->getState(StateInterface::PARSE));
    $this->assertInstanceOf(StateInterface::class, $feed->getState(StateInterface::PROCESS));
    $this->assertInstanceOf(CleanStateInterface::class, $feed->getState(StateInterface::CLEAN));
    $this->assertInstanceOf(StateInterface::class, $feed->getState(StateInterface::CLEAR));
  }

  /**
   * @covers ::getState
   */
  public function testGetStateAfterSettingStateToNull() {
    $feed = $this->createFeed($this->feedType->id());

    // Explicitly set a state to NULL.
    $feed->setState(StateInterface::PARSE, NULL);
    $feed->saveStates();

    // Assert that getState() still returns an instance of StateInterface.
    $this->assertInstanceOf(StateInterface::class, $feed->getState(StateInterface::PARSE));
  }

  /**
   * @covers ::setState
   */
  public function testSetState() {
    $feed = $this->createFeed($this->feedType->id());

    // Mock a state object.
    $state = $this->createMock(StateInterface::class);

    // Set state on the fetch stage.
    $feed->setState(StateInterface::FETCH, $state);
    $this->assertSame($state, $feed->getState(StateInterface::FETCH));

    // Clear a state.
    $feed->setState(StateInterface::FETCH, NULL);
    $this->assertNotSame($state, $feed->getState(StateInterface::FETCH));
    $this->assertInstanceOf(StateInterface::class, $feed->getState(StateInterface::FETCH));
  }

  /**
   * @covers ::clearStates
   */
  public function testClearStates() {
    $feed = $this->createFeed($this->feedType->id());

    // Set a state.
    $state = $this->createMock(StateInterface::class);
    $feed->setState(StateInterface::FETCH, $state);
    $this->assertSame($state, $feed->getState(StateInterface::FETCH));

    // Clear states.
    $feed->clearStates();
    $this->assertNotSame($state, $feed->getState(StateInterface::FETCH));
  }

  /**
   * @covers ::saveStates
   */
  public function testSaveStates() {
    $feed = $this->createFeed($this->feedType->id());

    // Set a state.
    $state = $this->createMock(StateInterface::class);
    $feed->setState(StateInterface::FETCH, $state);

    // Save states.
    $feed->saveStates();
  }

  /**
   * @covers ::getItemCount
   */
  public function testGetItemCount() {
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Assert that no items were imported yet.
    $this->assertSame(0, $feed->getItemCount());

    // Now import.
    $feed->import();

    // And assert the result.
    $this->assertSame(6, $feed->getItemCount());
  }

  /**
   * @covers ::getConfigurationFor
   */
  public function testGetConfigurationFor() {
    $feed = $this->createFeed($this->feedType->id());

    // This test does not work with a data provider as that results into phpunit
    // passing an __PHP_Incomplete_Class.
    $classes = [
      FeedsPluginInterface::class,
      FetcherInterface::class,
      ParserInterface::class,
      ProcessorInterface::class,
    ];

    foreach ($classes as $class) {
      $plugin = $this->createMock($class);
      $plugin->expects($this->atLeastOnce())
        ->method('defaultFeedConfiguration')
        ->will($this->returnValue([]));

      $this->assertIsArray($feed->getConfigurationFor($plugin));
    }
  }

  /**
   * @covers ::setConfigurationFor
   */
  public function testSetConfigurationFor() {
    $feed = $this->createFeed($this->feedType->id());

    // This test does not work with a data provider as that results into phpunit
    // passing an __PHP_Incomplete_Class.
    $classes = [
      FeedsPluginInterface::class,
      FetcherInterface::class,
      ParserInterface::class,
      ProcessorInterface::class,
    ];

    foreach ($classes as $class) {
      $plugin = $this->createMock($class);
      $plugin->expects($this->atLeastOnce())
        ->method('defaultFeedConfiguration')
        ->will($this->returnValue([]));

      $feed->setConfigurationFor($plugin, [
        'foo' => 'bar',
      ]);
    }
  }

  /**
   * @covers ::postDelete
   */
  public function testPostDeleteWithFeedTypeMissing() {
    $feed = $this->createFeed($this->feedType->id());

    // Create variables that are expected later in the log message.
    $feed_label = $feed->label();
    $feed_type_id = $this->feedType->id();

    // Add a logger.
    $test_logger = new TestLogger();
    $this->container->get('logger.factory')->addLogger($test_logger);

    // Delete feed type and reload feed.
    $this->feedType->delete();
    $feed = $this->reloadEntity($feed);

    $feed->postDelete($this->container->get('entity_type.manager')->getStorage('feeds_feed'), [$feed]);
    $logs = $test_logger->getMessages();
    $expected_logs = [
      'Could not perform some post cleanups for feed ' . $feed_label . ' because of the following error: The feed type "' . $feed_type_id . '" for feed 1 no longer exists.',
    ];
    $this->assertEquals($expected_logs, $logs);
  }

  /**
   * @covers ::setActive
   * @covers ::isActive
   */
  public function testSetActive() {
    $feed = $this->createFeed($this->feedType->id());

    // Activate feed.
    $feed->setActive(TRUE);
    $this->assertSame(TRUE, $feed->isActive());

    // Deactivate feed.
    $feed->setActive(FALSE);
    $this->assertSame(FALSE, $feed->isActive());

    // Activate feed again.
    $feed->setActive(TRUE);
    $this->assertSame(TRUE, $feed->isActive());
  }

  /**
   * @covers ::lock
   * @covers ::unlock
   * @covers ::isLocked
   */
  public function testLock() {
    $feed = $this->createFeed($this->feedType->id());

    // Lock feed.
    $feed->lock();
    $this->assertSame(TRUE, $feed->isLocked());

    // Unlock feed.
    $feed->unlock();
    $this->assertSame(FALSE, $feed->isLocked());

    // Lock feed again.
    $feed->lock();
    $this->assertSame(TRUE, $feed->isLocked());
  }

}
