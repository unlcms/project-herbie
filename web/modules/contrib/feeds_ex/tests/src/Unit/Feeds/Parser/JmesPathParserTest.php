<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\JmesPathParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\JsonUtility;
use JmesPath\AstRuntime;
use RuntimeException;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JmesPathParser
 * @group feeds_ex
 */
class JmesPathParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new JsonUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new JmesPathParser($configuration, 'jmespath', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());

    // Set JMESPath runtime factory.
    $factoryMock = $this->createMock('Drupal\feeds_ex\JmesRuntimeFactoryInterface');
    $factoryMock->expects($this->any())
      ->method('createRuntime')
      ->will($this->returnCallback(
        function () {
          return new AstRuntime();
        }
      ));
    $this->parser->setRuntimeFactory($factoryMock);
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items',
      ],
    ];
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
   * Tests a EUC-JP (Japanese) encoded file.
   *
   * This implicitly tests Base's encoding conversion.
   */
  public function testEucJpEncoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.json'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items',
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
   * Tests batch parsing.
   */
  public function testBatchParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => 'items',
      ],
      'line_limit' => 1,
    ];
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
        'value' => '@',
      ],
    ];
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
   * Tests JMESPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $expression = '!! ';
    $this->assertStringStartsWith('<pre>Syntax error at character', $this->invokeMethod($this->parser, 'validateExpression', [&$expression]));

    // Test that value was trimmed.
    $this->assertSame($expression, '!!', 'Value was trimmed.');

    // Empty string.
    $empty = '';
    $this->assertSame(NULL, $this->invokeMethod($this->parser, 'validateExpression', [&$empty]));
  }

  /**
   * Tests parsing invalid context expression.
   */
  public function testInvalidContextExpression() {
    $config = [
      'context' => [
        'value' => 'items',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([]));

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('The context expression must return an object or array.');
    $this->parser->parse($this->feed, new RawFetcherResult('{"items": "not an array"}', $this->fileSystem), $this->state);
  }

  /**
   * Tests parsing invalid JSON.
   */
  public function testInvalidJson() {
    $config = [
      'context' => [
        'value' => 'items',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([]));

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('The JSON is invalid.');
    $this->parser->parse($this->feed, new RawFetcherResult('invalid json', $this->fileSystem), $this->state);
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
