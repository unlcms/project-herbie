<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Feeds\Parser\OpmlParser;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\OpmlParser
 * @group feeds
 */
class OpmlParserTest extends FeedsUnitTestCase {

  /**
   * The Feeds parser plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Parser\OpmlParser
   */
  protected $parser;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

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
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->feedType = $this->createMock('Drupal\feeds\FeedTypeInterface');
    $configuration = ['feed_type' => $this->feedType];
    $this->parser = new OpmlParser($configuration, 'sitemap', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->feed = $this->createMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));

    $this->state = $this->createMock('Drupal\feeds\StateInterface');
  }

  /**
   * Tests parsing an OPML file that succeeds.
   *
   * @covers ::parse
   */
  public function testParse() {
    $file = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/tests/resources/opml-example.xml';
    $fetcher_result = new RawFetcherResult(file_get_contents($file), $this->getMockFileSystem());

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 13);
    $this->assertSame($result[0]->get('title'), 'CNET News.com');
    $this->assertSame($result[3]->get('xmlurl'), 'http://rss.news.yahoo.com/rss/tech');
    $this->assertSame($result[7]->get('htmlurl'), 'http://www.fool.com');
  }

  /**
   * Tests parsing an empty feed.
   *
   * @covers ::parse
   */
  public function testEmptyFeed() {
    $this->expectException(EmptyFeedException::class);
    $this->parser->parse($this->feed, new RawFetcherResult('', $this->getMockFileSystem()), $this->state);
  }

  /**
   * @covers ::getMappingSources
   */
  public function testGetMappingSources() {
    // Not really much to test here.
    $this->assertSame(count($this->parser->getMappingSources()), 5);
  }

}
