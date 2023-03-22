<?php

namespace Drupal\Tests\feeds\Unit\Event;

use Drupal\feeds\Event\ParseEvent;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Event\ParseEvent
 * @group feeds
 */
class ParseEventTest extends FeedsUnitTestCase {

  /**
   * @covers ::getFetcherResult
   * @covers ::getParserResult
   */
  public function test() {
    $feed = $this->createMock('Drupal\feeds\FeedInterface');
    $fetcher_result = $this->createMock('Drupal\feeds\Result\FetcherResultInterface');
    $parser_result = $this->createMock('Drupal\feeds\Result\ParserResultInterface');
    $event = new ParseEvent($feed, $fetcher_result);

    $this->assertSame($fetcher_result, $event->getFetcherResult());

    $event->setParserResult($parser_result);
    $this->assertSame($parser_result, $event->getParserResult());
  }

}
