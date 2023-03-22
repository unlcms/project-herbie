<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\JsonPathParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\JsonUtility;
use Exception;
use RuntimeException;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JsonPathParser
 * @group feeds_ex
 */
class JsonPathParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new JsonUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new JsonPathParser($configuration, 'jsonpath', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '$.items.*',
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
   * Tests parsing error handling.
   */
  public function testErrorHandling() {
    // Parse some invalid JSON.
    json_decode('\\"asdfasfd');

    $errors = $this->invokeMethod($this->parser, 'getErrors');
    $this->assertSame(3, $errors[0]['severity']);
  }

  /**
   * Tests batch parsing.
   */
  public function testBatchParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '$.items.*',
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

    // We should be out of items.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(0, $result);
  }

  /**
   * Tests parsing with a root object.
   */
  public function testRootContext() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'items[0].title',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(1, $result);
    $this->assertEquals('I am a title0', $result[0]->get('title'));
  }

  /**
   * Tests JSONPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $expression = '!! ';
    $this->assertStringStartsWith('Unable to parse token !! in expression', $this->invokeMethod($this->parser, 'validateExpression', [&$expression]));

    // Test that value was trimmed.
    $this->assertSame($expression, '!!', 'Value was trimmed.');
  }

  /**
   * Tests parsing invalid JSON.
   */
  public function testInvalidJson() {
    $config = [
      'context' => [
        'value' => '$.items[asdfasdf]',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([]));

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('The JSON is invalid.');
    $this->parser->parse($this->feed, new RawFetcherResult('invalid json', $this->fileSystem), $this->state);
  }

  /**
   * Tests log messages when using invalid JSON.
   *
   * @todo Feeds log is gone.
   */
  public function _testInvalidJsonLogMessages() {
    $config = [
      'context' => [
        'value' => '$.items[asdfasdf]',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([]));

    try {
      $this->parser->parse($this->feed, new RawFetcherResult('invalid json', $this->fileSystem), $this->state);
    }
    catch (Exception $e) {
      // Ignore any exceptions.
    }

    $log_messages = $this->feed->getLogMessages();
    $this->assertCount(1, $log_messages);
    $this->assertSame($log_messages[0]['message'], 'Syntax error');
    $this->assertSame($log_messages[0]['type'], 'feeds_ex');
    $this->assertSame($log_messages[0]['severity'], 3);
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
