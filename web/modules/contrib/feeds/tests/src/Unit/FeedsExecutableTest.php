<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds\FeedsExecutable;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\FeedsExecutable
 * @group feeds
 */
class FeedsExecutableTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $this->dispatcher = new EventDispatcher();
    $messenger = $this->createMock(MessengerInterface::class);

    $this->executable = new FeedsExecutable($entity_type_manager, $this->dispatcher, $this->getMockedAccountSwitcher(), $messenger);
    $this->executable->setStringTranslation($this->getStringTranslationStub());

    $this->feed = $this->createMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('id')
      ->will($this->returnValue(10));
    $this->feed->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_feed'));
  }

  /**
   * @covers ::doFetch
   * @covers ::doParse
   * @covers ::doProcess
   */
  public function testImport() {
    $this->addDefaultEventListeners();

    $this->feed->expects($this->once())
      ->method('progressParsing')
      ->willReturn(StateInterface::BATCH_COMPLETE);
    $this->feed->expects($this->once())
      ->method('progressFetching')
      ->willReturn(StateInterface::BATCH_COMPLETE);
    $this->feed->expects($this->once())
      ->method('progressCleaning')
      ->willReturn(StateInterface::BATCH_COMPLETE);

    $this->feed->expects($this->exactly(3))
      ->method('saveStates');

    $this->executable->processItem($this->feed, FeedsExecutable::BEGIN);
  }

  /**
   * Adds default listeners to event dispatcher.
   */
  protected function addDefaultEventListeners() {
    $fetcher_result = $this->createMock(FetcherResultInterface::class);
    $parser_result = new ParserResult();
    $parser_result->addItem($this->createMock(ItemInterface::class));

    $this->dispatcher->addListener(FeedsEvents::FETCH, function (FetchEvent $event) use ($fetcher_result) {
      $event->setFetcherResult($fetcher_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PARSE, function (ParseEvent $event) use ($fetcher_result, $parser_result) {
      $this->assertSame($event->getFetcherResult(), $fetcher_result);
      $event->setParserResult($parser_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PROCESS, function (ProcessEvent $event) use ($parser_result) {
      $this->assertInstanceOf(ItemInterface::class, $event->getItem());
    });
  }

}
