<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Feeds\Fetcher\Form\UploadFetcherForm;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\Form\UploadFetcherForm
 * @group feeds
 */
class UploadFetcherFormTest extends FeedsUnitTestCase {

  /**
   * Tests the configuration form.
   *
   * @covers ::buildConfigurationForm
   * @covers ::validateConfigurationForm
   */
  public function testConfigurationForm() {
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $file_system = $this->prophesize(FileSystemInterface::class);
    $file_system->prepareDirectory('vfs://feeds/uploads', FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)->will(function () {
      return mkdir('vfs://feeds/uploads');
    });
    $file_system->prepareDirectory('vfs://noroot', FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)->will(function () {
      return mkdir('vfs://noroot');
    });
    $stream_wrapper_manager = $this->prophesize(StreamWrapperManagerInterface::class);
    $stream_wrapper_manager->getWrappers(StreamWrapperInterface::WRITE_VISIBLE)->willReturn([]);

    $container->set('file_system', $file_system->reveal());
    $container->set('stream_wrapper_manager', $stream_wrapper_manager->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());

    $form_object = UploadFetcherForm::create($container);

    $plugin = $this->prophesize(FeedsPluginInterface::class);

    $form_object->setPlugin($plugin->reveal());

    $form_state = new FormState();

    $form = $form_object->buildConfigurationForm([], $form_state);
    $form['directory']['#parents'] = ['directory'];

    // Validate.
    $form_state->setValue(['directory'], 'vfs://feeds/uploads');
    $form_state->setValue(['allowed_extensions'], 'csv');

    $form_object->validateConfigurationForm($form, $form_state);
    $this->assertSame(0, count($form_state->getErrors()));

    // Validate.
    $form_state->setValue(['directory'], 'vfs://noroot');
    $form_object->validateConfigurationForm($form, $form_state);
    $this->assertSame('The chosen directory does not exist and attempts to create it failed.', (string) $form_state->getError($form['directory']));
  }

}
