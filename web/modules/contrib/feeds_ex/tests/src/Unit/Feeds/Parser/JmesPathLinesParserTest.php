<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds_ex\Feeds\Parser\JmesPathLinesParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\JsonUtility;
use JmesPath\AstRuntime;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JmesPathLinesParser
 * @group feeds_ex
 *
 * The test methods are in
 * \Drupal\Tests\feeds_ex\Unit\Feeds\Parser\JsonPathLinesParserTest.
 */
class JmesPathLinesParserTest extends JsonPathLinesParserTest {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new JsonUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new JmesPathLinesParser($configuration, 'jmespathlines', [], $utility);
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

    $this->fetcherResult = new FetcherResult($this->moduleDir . '/tests/resources/test.jsonl');
  }

}
