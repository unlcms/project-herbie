<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\QueryPathHtmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\XmlUtility;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathHtmlParser
 * @group feeds_ex
 */
class QueryPathHtmlParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new XmlUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new QueryPathHtmlParser($configuration, 'querypathhtml', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.post',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'p',
          'attribute' => '',
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    $this->assertSame('I am a title<thing>Stuff</thing>', $result[0]->get('title'));
    $this->assertSame('I am a description0', $result[0]->get('description'));
    $this->assertSame('I am a title1', $result[1]->get('title'));
    $this->assertSame('I am a description1', $result[1]->get('description'));
    $this->assertSame('I am a title2', $result[2]->get('title'));
    $this->assertSame('I am a description2', $result[2]->get('description'));
  }

  /**
   * Tests raw.
   */
  public function testRaw() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.post',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'p',
          'attribute' => '',
          'raw' => TRUE,
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    $this->assertSame('I am a title<thing>Stuff</thing>', $result[0]->get('title'));
    $this->assertSame('<p>I am a description0</p>', $result[0]->get('description'));
    $this->assertSame('I am a title1', $result[1]->get('title'));
    $this->assertSame('<p>I am a description1</p>', $result[1]->get('description'));
    $this->assertSame('I am a title2', $result[2]->get('title'));
    $this->assertSame('<p>I am a description2</p>', $result[2]->get('description'));
  }

  /**
   * Tests inner xml.
   */
  public function testInner() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.post',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'p',
          'attribute' => '',
          'raw' => TRUE,
          'inner' => TRUE,
        ],
      ]));

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(3, $result);

    $this->assertSame('I am a title<thing>Stuff</thing>', $result[0]->get('title'));
    $this->assertSame('I am a description0', $result[0]->get('description'));
    $this->assertSame('I am a title1', $result[1]->get('title'));
    $this->assertSame('I am a description1', $result[1]->get('description'));
    $this->assertSame('I am a title2', $result[2]->get('title'));
    $this->assertSame('I am a description2', $result[2]->get('description'));
  }

  /**
   * Tests grabbing an attribute.
   */
  public function testAttributeParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.post',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'name' => 'Title',
          'value' => 'h3',
          'attribute' => 'attr',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'p',
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
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCp866Encoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.post',
      ],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ],
        'description' => [
          'label' => 'Paragraph',
          'value' => 'p',
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
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '.post',
      ],
      'source_encoding' => ['EUC-JP'],
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
          'attribute' => '',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'p',
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
