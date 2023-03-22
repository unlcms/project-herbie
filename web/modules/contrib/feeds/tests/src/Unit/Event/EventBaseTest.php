<?php

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Event\EventBase
 * @group feeds
 */
class EventBaseTest extends FeedsUnitTestCase {

  /**
   * @covers ::getFeed
   */
  public function testGetFeed() {
    $feed = $this->createMock('Drupal\feeds\FeedInterface');
    $event = $this->getMockForAbstractClass('Drupal\feeds\Event\EventBase', [$feed]);
    $this->assertSame($feed, $event->getFeed());
  }

}
