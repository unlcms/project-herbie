<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\XmlUtility;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser
 * @group feeds_ex
 */
class QueryPathXmlParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new XmlUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new QueryPathXmlParser($configuration, 'querypathxml', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('I am a description' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests raw parsing.
   */
  public function testRaw() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
          'raw' => TRUE,
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertXmlStringEqualsXmlString('<description><text>I am a description' . $delta . '</text></description>', $item->get('description'));
    }
  }

  /**
   * Tests inner xml.
   */
  public function testInner() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
          'raw' => TRUE,
          'inner' => TRUE,
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertXmlStringEqualsXmlString('<text>I am a description' . $delta . '</text>', $item->get('description'));
    }
  }

  /**
   * Tests grabbing an attribute.
   */
  public function testAttributeParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'title',
          'attribute' => 'attr',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('attribute' . $delta, $item->get('title'));
      $this->assertSame('I am a description' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests grabbing multiple attributes.
   */
  public function testMultipleAttributeParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items thing',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'url' => [
          'name' => 'URL',
          'value' => 'img',
          'attribute' => 'src',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(1, $result);

    $url = $result[0]->get('url');
    $this->assertCount(2, $url);
    $this->assertSame($url[0], 'http://drupal.org');
    $this->assertSame($url[1], 'http://drupal.org/project/feeds_ex');
  }

  /**
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCp866Encoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('Я название' . $delta, $item->get('title'));
      $this->assertSame('Я описание' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests a EUC-JP (Japanese) encoded file without the encoding declaration.
   *
   * This implicitly tests Base's encoding conversion.
   */
  public function testEucJpEncodedNoDeclaration() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
      'source_encoding' => ['EUC-JP'],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
          'attribute' => '',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('私はタイトルです' . $delta, $item->get('title'));
      $this->assertSame('私が説明してい' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests that batch parsing works.
   */
  public function testBatchParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items item',
      ],
      'line_limit' => 1,
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'title',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
          'attribute' => '',
        ],
      ]));

    foreach (range(0, 2) as $delta) {
      $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
      $this->assertCount(1, $result);
      $this->assertSame('I am a title' . $delta, $result[0]->get('title'));
      $this->assertSame('I am a description' . $delta, $result[0]->get('description'));
    }

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(0, $result);
  }

  /**
   * Tests QueryPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $expression = '!! ';
    $this->assertSame('CSS selector is not well formed.', (string) $this->invokeMethod($this->parser, 'validateExpression', [&$expression]));

    // Test that value was trimmed.
    $this->assertSame($expression, '!!', 'Value was trimmed.');

    // Empty.
    $empty = '';
    $this->assertSame(NULL, $this->invokeMethod($this->parser, 'validateExpression', [&$empty]));
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([]));
    $this->parser->parse($this->feed, new RawFetcherResult(' ', $this->fileSystem), $this->state);
    $this->assertEmptyFeedMessage($this->parser->getMessenger()->getMessages());
  }

}
