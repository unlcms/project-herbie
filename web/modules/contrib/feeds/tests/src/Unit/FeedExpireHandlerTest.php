<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\FeedExpireHandler;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\State;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\FeedExpireHandler
 * @group feeds
 */
class FeedExpireHandlerTest extends FeedsUnitTestCase {

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
   * The handler to test.
   *
   * @var \Drupal\feeds\FeedExpireHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->feed = $this->createMock(FeedInterface::class);
    $this->handler = $this->getMockBuilder(FeedExpireHandler::class)
      ->setMethods(['getExpiredIds', 'batchSet'])
      ->setConstructorArgs([
        $this->dispatcher,
        $this->createMock(Connection::class),
      ])
      ->getMock();
    $this->handler->setStringTranslation($this->createMock(TranslationInterface::class));
    $this->handler->setMessenger($this->createMock(MessengerInterface::class));
  }

  /**
   * @covers ::startBatchExpire
   */
  public function testBatchExpire() {
    $this->feed->expects($this->once())
      ->method('lock')
      ->will($this->returnValue($this->feed));

    $this->handler->expects($this->once())
      ->method('getExpiredIds')
      ->will($this->returnValue([1]));

    $this->handler->startBatchExpire($this->feed);
  }

  /**
   * @covers ::expireItem
   */
  public function testExpireItem() {
    $this->feed
      ->expects($this->exactly(2))
      ->method('progressExpiring')
      ->will($this->onConsecutiveCalls(0.5, 1.0));

    $result = $this->handler->expireItem($this->feed, 1);
    $this->assertSame($result, 0.5);
    $result = $this->handler->expireItem($this->feed, 2);
    $this->assertSame($result, 1.0);
  }

  /**
   * @covers ::expireItem
   */
  public function testExpireItemWithException() {
    $this->dispatcher->addListener(FeedsEvents::EXPIRE, function ($event) {
      throw new \Exception();
    });

    $this->feed
      ->expects($this->once())
      ->method('clearStates');

    $this->expectException(\Exception::class);
    $this->handler->expireItem($this->feed, 1);
  }

  /**
   * @covers ::postExpire
   */
  public function testPostExpire() {
    $state = new State();
    $state->total = 1;

    $this->feed->expects($this->once())
      ->method('getState')
      ->will($this->returnValue($state));

    $this->handler->postExpire($this->feed);
  }

}
