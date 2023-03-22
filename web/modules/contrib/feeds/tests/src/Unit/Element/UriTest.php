<?php

namespace Drupal\Tests\feeds\Unit\Element {

  use Drupal\Core\DependencyInjection\ContainerBuilder;
  use Drupal\Core\Form\FormState;
  use Drupal\Core\StreamWrapper\StreamWrapperManager;
  use Drupal\feeds\Element\Uri;
  use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

  /**
   * @coversDefaultClass \Drupal\feeds\Element\Uri
   * @group feeds
   */
  class UriTest extends FeedsUnitTestCase {

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      parent::setUp();

      // Set a stub for the string translation service.
      $container = new ContainerBuilder();
      $container->set('string_translation', $this->getStringTranslationStub());
      \Drupal::setContainer($container);
    }

    /**
     * Tests validation.
     */
    public function testValidation() {
      $complete_form = [];
      $form_state = new FormState();
      $stream_wrapper_manager = new StreamWrapperManager();

      $element_object = new Uri([], '', []);

      $element = ['#value' => ' public://test', '#parents' => ['element']];
      $element += $element_object->getInfo();
      Uri::validateUrl($element, $form_state, $complete_form, $stream_wrapper_manager);
      $this->assertSame('public://test', $form_state->getValue('element'));

      $element = ['#value' => '', '#parents' => ['element']];
      $element += $element_object->getInfo();
      Uri::validateUrl($element, $form_state, $complete_form, $stream_wrapper_manager);
      $this->assertSame('', $form_state->getValue('element'));

      $element = ['#value' => '@@', '#parents' => ['element']];
      $element += $element_object->getInfo();
      Uri::validateUrl($element, $form_state, $complete_form, $stream_wrapper_manager);
      $this->assertSame('@@', $form_state->getValue('element'));
      $this->assertSame('The URI <em class="placeholder">@@</em> is not valid.', (string) $form_state->getError($element));
      $form_state->clearErrors();

      $element = [
        '#value' => 'badscheme://foo',
        '#parents' => ['element'],
        '#allowed_schemes' => ['public'],
      ];
      $element += $element_object->getInfo();
      Uri::validateUrl($element, $form_state, $complete_form, $stream_wrapper_manager);
      $this->assertSame('The scheme <em class="placeholder">badscheme</em> is invalid. Available schemes: public.', (string) $form_state->getError($element));
    }

  }
}

namespace {

  use Drupal\Component\Render\FormattableMarkup;

  if (!function_exists('t')) {

    /**
     * Stub for t() function.
     */
    function t($string, array $args = []) {
      return new FormattableMarkup($string, $args);
    }

  }

}
