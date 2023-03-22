<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser\Form;

use Drupal\Core\Form\FormState;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Feeds\Parser\Form\CsvParserForm;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\Form\CsvParserForm
 * @group feeds
 */
class CsvParserFormTest extends FeedsUnitTestCase {

  /**
   * @covers ::buildConfigurationForm
   */
  public function testConfigurationForm() {
    $form_state = new FormState();
    $plugin = $this->prophesize(FeedsPluginInterface::class);

    $form_object = new CsvParserForm();
    $form_object->setStringTranslation($this->getStringTranslationStub());
    $form_object->setPlugin($plugin->reveal());

    $form = $form_object->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 2);
  }

}
