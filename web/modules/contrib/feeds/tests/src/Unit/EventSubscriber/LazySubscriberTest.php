<?php

namespace Drupal\Tests\feeds\Unit\EventSubscriber;

use Drupal\feeds\EventSubscriber\LazySubscriber;
use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Result\ParserResult;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\EventSubscriber\LazySubscriber
 * @group feeds
 */
class LazySubscriberTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * A second event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $explodingDispatcher;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The state object.
   *
   * @var \Drupal\feeds\StateInterface
   */
  protected $state;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The Feeds fetcher plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface
   */
  protected $fetcher;

  /**
   * The Feeds parser plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\Parser\ParserInterface
   */
  protected $parser;

  /**
   * The Feeds processor plugin.
   *
   * @var \Drupal\feeds\Plugin\Type\Processor\ProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();

    // Dispatcher used to verify things only get called once.
    $this->explodingDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->explodingDispatcher->expects($this->any())
      ->method('addListener')
      ->will($this->throwException(new \Exception()));

    $this->state = $this->createMock('Drupal\feeds\StateInterface');
    $this->feed = $this->createMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getState')
      ->will($this->returnValue($this->state));
    $this->feedType = $this->createMock('Drupal\feeds\FeedTypeInterface');
    $this->feedType->expects($this->any())
      ->method('getMappedSources')
      ->will($this->returnValue([]));

    $this->fetcher = $this->createMock('Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface');
    $this->parser = $this->createMock('Drupal\feeds\Plugin\Type\Parser\ParserInterface');
    $this->processor = $this->createMock('Drupal\feeds\Plugin\Type\Processor\ProcessorInterface');

    $this->feed
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));
  }

  /**
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $events = LazySubscriber::getSubscribedEvents();
    $this->assertSame(3, count($events));
  }

  /**
   * @covers ::onInitImport
   */
  public function testOnInitImport() {
    $fetcher_result = $this->createMock('Drupal\feeds\Result\FetcherResultInterface');
    $parser_result = new ParserResult();
    $parser_result->addItem(new DynamicItem());

    $this->fetcher->expects($this->once())
      ->method('fetch')
      ->with($this->feed, $this->state)
      ->will($this->returnValue($fetcher_result));
    $this->parser->expects($this->once())
      ->method('parse')
      ->with($this->feed, $fetcher_result, $this->state)
      ->will($this->returnValue($parser_result));
    $this->processor->expects($this->once())
      ->method('process');

    $this->feedType->expects($this->once())
      ->method('getFetcher')
      ->will($this->returnValue($this->fetcher));
    $this->feedType->expects($this->once())
      ->method('getParser')
      ->will($this->returnValue($this->parser));
    $this->feedType->expects($this->once())
      ->method('getProcessor')
      ->will($this->returnValue($this->processor));

    $subscriber = new LazySubscriber();

    // Fetch.
    $subscriber->onInitImport(new InitEvent($this->feed, 'fetch'), FeedsEvents::INIT_IMPORT, $this->dispatcher);
    $fetch_event = $this->dispatcher->dispatch(new FetchEvent($this->feed), FeedsEvents::FETCH);
    $this->assertSame($fetcher_result, $fetch_event->getFetcherResult());

    // Parse.
    $subscriber->onInitImport(new InitEvent($this->feed, 'parse'), FeedsEvents::INIT_IMPORT, $this->dispatcher);
    $parse_event = $this->dispatcher->dispatch(new ParseEvent($this->feed, $fetcher_result), FeedsEvents::PARSE);
    $this->assertSame($parser_result, $parse_event->getParserResult());

    // Process.
    $subscriber->onInitImport(new InitEvent($this->feed, 'process'), FeedsEvents::INIT_IMPORT, $this->dispatcher);
    foreach ($parse_event->getParserResult() as $item) {
      $this->dispatcher->dispatch(new ProcessEvent($this->feed, $item), FeedsEvents::PROCESS);
    }

    // Call again.
    $subscriber->onInitImport(new InitEvent($this->feed, 'fetch'), FeedsEvents::INIT_IMPORT, $this->explodingDispatcher);
  }

  /**
   * @covers ::onInitClear
   */
  public function testOnInitClear() {
    $clearable = $this->createMock('Drupal\feeds\Plugin\Type\ClearableInterface');
    $clearable->expects($this->exactly(2))
      ->method('clear')
      ->with($this->feed);

    $this->feedType->expects($this->once())
      ->method('getPlugins')
      ->will($this->returnValue([$clearable, $this->dispatcher, $clearable]));

    $subscriber = new LazySubscriber();

    $subscriber->onInitClear(new InitEvent($this->feed), FeedsEvents::INIT_CLEAR, $this->dispatcher);
    $this->dispatcher->dispatch(new ClearEvent($this->feed), FeedsEvents::CLEAR);

    // Call again.
    $subscriber->onInitClear(new InitEvent($this->feed), FeedsEvents::INIT_CLEAR, $this->explodingDispatcher);
  }

  /**
   * @covers ::onInitExpire
   */
  public function testOnInitExpire() {
    $this->feedType->expects($this->once())
      ->method('getProcessor')
      ->will($this->returnValue($this->processor));
    $this->processor->expects($this->once())
      ->method('expireItem')
      ->with($this->feed);

    $subscriber = new LazySubscriber();
    $subscriber->onInitExpire(new InitEvent($this->feed), FeedsEvents::INIT_IMPORT, $this->dispatcher);
    $this->dispatcher->dispatch(new ExpireEvent($this->feed, 1234), FeedsEvents::EXPIRE);

    // Call again.
    $subscriber->onInitExpire(new InitEvent($this->feed), FeedsEvents::INIT_IMPORT, $this->explodingDispatcher);
  }

}
