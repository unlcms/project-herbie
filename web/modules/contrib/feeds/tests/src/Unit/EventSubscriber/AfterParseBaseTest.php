<?php

namespace Drupal\Tests\feeds\Unit\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\EventSubscriber\AfterParseBase
 * @group feeds
 */
class AfterParseBaseTest extends FeedsUnitTestCase {

  /**
   * The mocked subscriber object.
   *
   * @var \Drupal\feeds\EventSubscriber\AfterParseBase
   */
  protected $subscriber;

  /**
   * The parser result.
   *
   * @var \Drupal\feeds\Result\ParserResult
   */
  protected $parserResult;

  /**
   * The mocked parse event.
   *
   * @var \Drupal\feeds\Event\ParseEvent
   */
  protected $event;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create the event subscriber.
    $this->subscriber = $this->getMockBuilder(AfterParseBase::class)
      ->onlyMethods(['alterItem'])
      ->getMock();

    // Create a parser result.
    $this->parserResult = new ParserResult();

    // Create the event that returns the parser result.
    $this->event = $this->getMockBuilder(ParseEvent::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getParserResult'])
      ->getMock();
    $this->event->expects($this->any())
      ->method('getParserResult')
      ->willReturn($this->parserResult);
  }

  /**
   * Tests that a list of items can get manipulated.
   *
   * @covers ::afterParse
   */
  public function testAfterParse() {
    // Create two items and add these to the parser result.
    $item1 = new DynamicItem();
    $item1->set('title', 'Foo');
    $item2 = new DynamicItem();
    $item2->set('title', 'Bar');
    $this->parserResult->addItems([$item1, $item2]);

    // Implement AfterParseBase::alterItem() by adding a '1' to each item's
    // title.
    $this->subscriber->expects($this->exactly(2))
      ->method('alterItem')
      ->will($this->returnCallback(function (ItemInterface $item, ParseEvent $event) {
        $item->set('title', $item->get('title') . '1');
      }));

    // Run subscriber.
    $this->subscriber->afterParse($this->event);
    // Assert that each item got a '1' added.
    $this->assertEquals('Foo1', $item1->get('title'));
    $this->assertEquals('Bar1', $item2->get('title'));
  }

  /**
   * Tests removing items by throwing a SkipItemException.
   *
   * @covers ::afterParse
   */
  public function testSkippingItems() {
    // Create a few items.
    for ($i = 1; $i <= 5; $i++) {
      $item = new DynamicItem();
      $item->set('id', $i);
      $this->parserResult->addItem($item);
    }

    // Implement AfterParseBase::alterItem() and throw an exception on items 3
    // and 5.
    $this->subscriber->expects($this->exactly(5))
      ->method('alterItem')
      ->will($this->returnCallback(function (ItemInterface $item, ParseEvent $event) {
        switch ($item->get('id')) {
          case 3:
          case 5:
            throw new SkipItemException();
        }
      }));

    // Run subscriber.
    $this->subscriber->afterParse($this->event);

    // Check which items are still on the parser result.
    $this->assertCount(3, $this->parserResult);
    $expected = [1, 2, 4];
    $i = 0;
    foreach ($this->parserResult as $item) {
      $this->assertEquals($expected[$i], $item->get('id'));
      $i++;
    }
  }

  /**
   * Tests that if applies() returns false, no items are altered.
   *
   * @covers ::afterParse
   */
  public function testApplies() {
    $subscriber = $this->getMockBuilder(AfterParseBase::class)
      ->onlyMethods(['applies', 'alterItem'])
      ->getMock();

    // Create a few items.
    for ($i = 1; $i <= 3; $i++) {
      $item = new DynamicItem();
      $item->set('id', $i);
      $this->parserResult->addItem($item);
    }

    $subscriber->expects($this->never())
      ->method('alterItem');

    $subscriber->expects($this->once())
      ->method('applies')
      ->willReturn(FALSE);

    // Run subscriber.
    $subscriber->afterParse($this->event);
  }

  /**
   * Tests that the event subscriber is properly called.
   *
   * @covers ::getSubscribedEvents
   */
  public function testDispatch() {
    // Create a few items.
    for ($i = 1; $i <= 3; $i++) {
      $item = new DynamicItem();
      $item->set('id', $i);
      $this->parserResult->addItem($item);
    }

    $this->subscriber->expects($this->exactly(3))
      ->method('alterItem');

    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber($this->subscriber);

    // Dispatch the event.
    $dispatcher->dispatch($this->event, FeedsEvents::PARSE);
  }

}
