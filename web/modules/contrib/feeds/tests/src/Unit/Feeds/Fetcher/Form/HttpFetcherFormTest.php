<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormState;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm
 * @group feeds
 */
class HttpFetcherFormTest extends FeedsUnitTestCase {

  /**
   * Tests the configuration form.
   *
   * @covers ::buildConfigurationForm
   */
  public function testConfigurationForm() {
    $form_object = new HttpFetcherForm();

    $form_object->setPlugin($this->createMock(FeedsPluginInterface::class));

    $form_object->setStringTranslation($this->getStringTranslationStub());

    $form = $form_object->buildConfigurationForm([], new FormState());
    $this->assertIsArray($form);
    $this->assertNotEmpty($form);
  }

}
