<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\HtmlParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\XmlUtility;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\HtmlParser
 * @group feeds_ex
 */
class HtmlParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new XmlUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new HtmlParser($configuration, 'html', [], $utility);
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
        'value' => '//div[@class="post"]',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'p',
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
   * Tests getting the raw value.
   */
  public function testRaw() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'p',
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
   * Tests innerxml.
   */
  public function testInner() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'p',
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
   * Tests parsing a CP866 (Russian) encoded file.
   */
  public function testCp866Encoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_ru.html'), $this->fileSystem);

    $config = [
      'context' => [
        'value' => '//div[@class="post"]',
      ],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'p',
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
        'value' => '//div[@class="post"]',
      ],
      'source_encoding' => ['EUC-JP'],
    ] + $this->parser->defaultConfiguration();
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'h3',
        ],
        'description' => [
          'label' => 'Description',
          'value' => 'p',
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
   * Tests that the link property is set.
   *
   * @todo turned off, because unsure if this is still needed.
   */
  public function _testLinkIsSet() {
    $this->setProperty($this->feed, 'config', [
      'FeedsFileFetcher' => [
        'source' => 'file fetcher source path',
      ],
    ]);

    $config = ['context' => ['value' => '/beep']];

    $result = $this->parser->parse($this->feed, new RawFetcherResult('<?xml version="1.0" encoding="UTF-8"?><item></item>', $this->fileSystem));
    $this->assertSame($result->link, 'file fetcher source path');
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
