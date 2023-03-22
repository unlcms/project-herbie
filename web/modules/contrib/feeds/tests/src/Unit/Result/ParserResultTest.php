<?php

namespace Drupal\Tests\feeds\Unit\Result;

use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Result\ParserResult
 * @group feeds
 */
class ParserResultTest extends FeedsUnitTestCase {

  /**
   * @covers ::addItem
   */
  public function testAddItem() {
    $result = new ParserResult();

    // Create some items.
    $item1 = $this->createMock(ItemInterface::class);
    $item2 = $this->createMock(ItemInterface::class);
    $item3 = $this->createMock(ItemInterface::class);

    // Add an item.
    $result->addItem($item1);
    $this->assertSame(1, $result->count());

    // Add another two items.
    $result->addItem($item2);
    $result->addItem($item3);
    $this->assertSame(3, $result->count());

    $this->assertSame($item1, $result->offsetGet(0));
    $this->assertSame($item2, $result->offsetGet(1));
    $this->assertSame($item3, $result->offsetGet(2));
  }

  /**
   * @covers ::addItems
   */
  public function testAddItems() {
    $result = new ParserResult();

    // Create some items.
    $item1 = $this->createMock(ItemInterface::class);
    $item2 = $this->createMock(ItemInterface::class);
    $item3 = $this->createMock(ItemInterface::class);

    $result->addItems([$item1, $item2, $item3]);
    $this->assertSame(3, $result->count());

    $this->assertSame($item1, $result->offsetGet(0));
    $this->assertSame($item2, $result->offsetGet(1));
    $this->assertSame($item3, $result->offsetGet(2));
  }

}
