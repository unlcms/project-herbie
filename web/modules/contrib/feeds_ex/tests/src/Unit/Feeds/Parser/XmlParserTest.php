<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\XmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\XmlUtility;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\XmlParser
 * @group feeds_ex
 */
class XmlParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new XmlUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new XmlParser($configuration, 'xml', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $contents = file_get_contents($this->moduleDir . '/tests/resources/test.xml');

    // Implicitly test that invalid characters are ignored and null bytes are
    // stripped.
    $contents = str_replace('I am a description', chr(0) . 'I am a description' . chr(11), $contents);
    $fetcher_result = new RawFetcherResult($contents, $this->fileSystem);

    $config = [
      'context' => [
        'value' => '/items/item',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
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
        'value' => '/items/item',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
          'raw' => TRUE,
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('<description><text>I am a description' . $delta . '</text></description>', $item->get('description'));
    }
  }

  /**
   * Tests simple parsing.
   */
  public function testInner() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '/items/item',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
          'raw' => TRUE,
          'inner' => TRUE,
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('<text>I am a description' . $delta . '</text>', $item->get('description'));
    }
  }

  /**
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCp866Encoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '/items/item',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
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
        'value' => '/items/item',
      ],
      'source_encoding' => ['EUC-JP'],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
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
   * Tests batching.
   */
  public function testBatching() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.xml'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '/items/item',
      ],
      'line_limit' => 1,
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'description',
        ],
      ]));

    foreach (range(0, 2) as $delta) {
      $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
      $this->assertCount(1, $result);
      $this->assertSame('I am a title' . $delta, $result[0]->get('title'));
      $this->assertSame('I am a description' . $delta, $result[0]->get('description'));
    }

    // Should be empty.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(0, $result);
  }

  /**
   * Tests that the link property is set.
   *
   * @todo replace setProperty().
   */
  public function _testLinkIsSet() {
    $this->setProperty($this->feed, 'config', [
      'FeedsFileFetcher' => [
        'source' => 'file fetcher source path',
      ],
    ]);

    $this->parser = $this->getParserInstance();
    $this->parser->setConfiguration(['context' => ['value' => '/beep']]);

    $result = $this->parser->parse($this->feed, new RawFetcherResult('<?xml version="1.0" encoding="UTF-8"?><item></item>', $this->fileSystem));
    $this->assertSame($result->link, 'file fetcher source path');
  }

  /**
   * Tests XPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $expression = '!! ';
    $this->assertSame('Invalid expression', (string) $this->invokeMethod($this->parser, 'validateExpression', [&$expression]));

    // Test that value was trimmed.
    $this->assertSame($expression, '!!', 'Value was trimmed.');

    // Unknown namespace.
    $unknown = 'thing:asdf';
    $this->assertSame(NULL, $this->invokeMethod($this->parser, 'validateExpression', [&$unknown]));

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
    $messages = $this->parser->getMessenger()->getMessages();
    $this->assertCount(1, $messages, 'The expected number of messages.');
    $this->assertSame((string) $messages[0]['message'], 'The feed is empty.', 'Message text is correct.');
    $this->assertSame($messages[0]['type'], 'warning', 'Message type is warning.');
    $this->assertFalse($messages[0]['repeat'], 'Repeat is set to false.');
  }

}
