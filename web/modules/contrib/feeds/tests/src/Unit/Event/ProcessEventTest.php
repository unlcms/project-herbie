<?php

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Event\ProcessEvent
 * @group feeds
 */
class ProcessEventTest extends FeedsUnitTestCase {

  /**
   * @covers ::getItem
   */
  public function testGetItem() {
    $feed = $this->createMock(FeedInterface::class);
    $item = $this->createMock(ItemInterface::class);
    $event = new ProcessEvent($feed, $item);

    $this->assertSame($item, $event->getItem());
  }

}
