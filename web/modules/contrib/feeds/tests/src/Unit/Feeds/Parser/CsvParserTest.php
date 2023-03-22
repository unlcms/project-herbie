<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Parser\CsvParser;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\CsvParser
 * @group feeds
 */
class CsvParserTest extends FeedsUnitTestCase {

  /**
   * The Feeds parser plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Parser\CsvParser
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

    $this->feedType = $this->createMock(FeedTypeInterface::class);
    $configuration = ['feed_type' => $this->feedType, 'line_limit' => 3];
    $this->parser = new CsvParser($configuration, 'csv', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->state = new State();

    $this->feed = $this->createMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));
  }

  /**
   * Tests parsing a CSV file that succeeds.
   *
   * @covers ::parse
   */
  public function testParse() {
    $this->feedType->method('getMappingSources')
      ->will($this->returnValue([]));

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->will($this->returnValue($this->parser->defaultFeedConfiguration()));

    $file = $this->resourcesPath() . '/csv/example.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('Header A'), '"1"');

    // Parse again. Tests batching.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('Header B'), "new\r\nline 2");
  }

  /**
   * Tests parsing with the "no_headers" option enabled.
   */
  public function testParseWithoutHeaders() {
    // Enable "no_headers" option.
    $config = [
      'no_headers' => TRUE,
    ] + $this->parser->defaultFeedConfiguration();

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->will($this->returnValue($config));

    // Provide mapping sources.
    $this->feedType->method('getMappingSources')
      ->will($this->returnValue([
        'column1' => [
          'label' => 'Column 1',
          'value' => 0,
          'machine_name' => 'column1',
        ],
        'column2' => [
          'label' => 'Column 2',
          'value' => 1,
          'machine_name' => 'column2',
        ],
      ]));

    $file = $this->resourcesPath() . '/csv/content.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    // Assert that there are three items.
    $this->assertSame(count($result), 3);
    // Assert that each item has the expected value on the machine name.
    $this->assertSame('guid', $result[0]->get('column1'));
    $this->assertSame('title', $result[0]->get('column2'));
    $this->assertSame('1', $result[1]->get('column1'));
    $this->assertSame('Lorem ipsum', $result[1]->get('column2'));
    $this->assertSame('2', $result[2]->get('column1'));
    $this->assertSame('Ut wisi enim ad minim veniam', $result[2]->get('column2'));
  }

  /**
   * Tests parsing an empty CSV file.
   *
   * @covers ::parse
   */
  public function testEmptyFeed() {
    $this->feedType->method('getMappingSources')
      ->will($this->returnValue([]));

    touch('vfs://feeds/empty_file');
    $result = new FetcherResult('vfs://feeds/empty_file');

    $this->expectException(EmptyFeedException::class);
    $this->parser->parse($this->feed, $result, $this->state);
  }

  /**
   * Tests parsing a file with a few extra blank lines.
   */
  public function testFeedWithExtraBlankLines() {
    $this->feedType->method('getMappingSources')
      ->will($this->returnValue([]));

    // Set an high line limit.
    $configuration = ['feed_type' => $this->feedType, 'line_limit' => 100];
    $this->parser = new CsvParser($configuration, 'csv', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->will($this->returnValue($this->parser->defaultFeedConfiguration()));

    $file = $this->resourcesPath() . '/csv/with-empty-lines.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(9, $result);

    // Parse again.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(0, $result);

    // Assert that parsing has finished.
    $this->assertEquals(StateInterface::BATCH_COMPLETE, $this->state->progress);
  }

  /**
   * @covers ::getMappingSources
   */
  public function testGetMappingSources() {
    // Not really much to test here.
    $this->assertSame([], $this->parser->getMappingSources());
  }

}
