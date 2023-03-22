<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Feeds\Parser\SitemapParser;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\State;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\SitemapParser
 * @group feeds
 */
class SitemapParserTest extends FeedsUnitTestCase {

  /**
   * The Feeds parser plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Parser\SitemapParser
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
    $this->parser = new SitemapParser($configuration, 'sitemap', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->state = new State();

    $this->feed = $this->createMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));
  }

  /**
   * Tests parsing a sitemap XML file that succeeds.
   *
   * @covers ::parse
   */
  public function testParse() {
    $file = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/tests/resources/sitemap-example.xml';
    $fetcher_result = new RawFetcherResult(file_get_contents($file), $this->getMockFileSystem());

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 5);
    $this->assertSame($result[0]->get('url'), 'http://www.example.com/');
    $this->assertSame($result[3]->get('priority'), '0.3');
  }

  /**
   * Tests parsing an invalid feed.
   *
   * @covers ::parse
   */
  public function testInvalidFeed() {
    $fetcher_result = new RawFetcherResult('beep boop', $this->getMockFileSystem());

    $this->expectException(\Exception::class);
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
  }

  /**
   * Tests parsing an empty feed.
   *
   * @covers ::parse
   */
  public function testEmptyFeed() {
    $result = new RawFetcherResult('', $this->getMockFileSystem());

    $this->expectException(EmptyFeedException::class);
    $this->parser->parse($this->feed, $result, $this->state);
  }

  /**
   * @covers ::getMappingSources
   */
  public function testGetMappingSources() {
    // Not really much to test here.
    $this->assertSame(count($this->parser->getMappingSources()), 4);
  }

}
