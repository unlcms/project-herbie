<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser\Form;

use Drupal\Core\Form\FormState;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Parser\Form\CsvParserFeedForm;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\Form\CsvParserFeedForm
 * @group feeds
 */
class CsvParserFeedFormTest extends FeedsUnitTestCase {

  /**
   * Tests the feed form.
   *
   * @covers ::buildConfigurationForm
   * @covers ::validateConfigurationForm
   * @covers ::submitConfigurationForm
   */
  public function testFeedForm() {
    $plugin = $this->createMock(FeedsPluginInterface::class);

    $feed = $this->prophesize(FeedInterface::class);
    $feed->getConfigurationFor($plugin)
      ->willReturn(['delimiter' => ',', 'no_headers' => FALSE]);
    $feed->setConfigurationFor($plugin, [
      'delimiter' => ';',
      'no_headers' => TRUE,
    ])->shouldBeCalled();

    $form_object = new CsvParserFeedForm();

    $form_object->setPlugin($plugin);
    $form_object->setStringTranslation($this->getStringTranslationStub());

    $form_state = new FormState();

    $form = $form_object->buildConfigurationForm([], $form_state, $feed->reveal());
    $this->assertIsArray($form);

    $form_state->setValues(['delimiter' => ';', 'no_headers' => TRUE]);

    $form_object->validateConfigurationForm($form, $form_state, $feed->reveal());

    $form_object->submitConfigurationForm($form, $form_state, $feed->reveal());
  }

}
