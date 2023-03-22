<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds_ex\Feeds\Parser\JsonPathLinesParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\JsonUtility;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JsonPathLinesParser
 * @group feeds_ex
 */
class JsonPathLinesParserTest extends ParserTestBase {

  /**
   * The fetcher result used during parsing.
   *
   * @var \Drupal\feeds\Result\FetcherResult
   */
  protected $fetcherResult;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new JsonUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new JsonPathLinesParser($configuration, 'jsonpathlines', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'name',
        ],
      ]));

    $this->fetcherResult = new FetcherResult($this->moduleDir . '/tests/resources/test.jsonl');
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $result = $this->parser->parse($this->feed, $this->fetcherResult, $this->state);
    $this->assertCount(4, $result);

    foreach (['Gilbert', 'Alexa', 'May', 'Deloise'] as $delta => $name) {
      $this->assertSame($name, $result[$delta]->get('title'));
    }
  }

  /**
   * Tests batch parsing.
   */
  public function testBatching() {
    $config = [
      'line_limit' => 1,
    ];
    $this->parser->setConfiguration($config);

    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([
        'title' => [
          'label' => 'Title',
          'value' => 'name',
        ],
      ]));

    foreach (['Gilbert', 'Alexa', 'May', 'Deloise'] as $name) {
      $result = $this->parser->parse($this->feed, $this->fetcherResult, $this->state);
      $this->assertCount(1, $result);
      $this->assertSame($result[0]->get('title'), $name);
    }

    // We should be out of items.
    $result = $this->parser->parse($this->feed, $this->fetcherResult, $this->state);
    $this->assertCount(0, $result);
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $this->feedType->expects($this->any())
      ->method('getCustomSources')
      ->will($this->returnValue([]));
    $this->parser->parse($this->feed, new FetcherResult($this->moduleDir . '/tests/resources/empty.txt'), $this->state);
    $this->assertEmptyFeedMessage($this->parser->getMessenger()->getMessages());
  }

}
