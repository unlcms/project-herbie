<?php

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\feeds\Event\FetchEvent;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Event\FetchEvent
 * @group feeds
 */
class FetchEventTest extends FeedsUnitTestCase {

  /**
   * @covers ::getFetcherResult
   */
  public function testGetFetcherResult() {
    $feed = $this->createMock('Drupal\feeds\FeedInterface');
    $result = $this->createMock('Drupal\feeds\Result\FetcherResultInterface');
    $event = new FetchEvent($feed);

    $event->setFetcherResult($result);
    $this->assertSame($result, $event->getFetcherResult());
  }

}
