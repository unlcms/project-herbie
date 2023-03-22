<?php

namespace Drupal\feeds_test_plugin\Feeds\Parser;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;

/**
 * Dummy parser to test integration with the Feeds mapping form.
 *
 * This parser deliberately does not extend
 * \Drupal\feeds\Feeds\Parser\ParserBase, in order to have tests for parsers
 * that only implement \Drupal\feeds\Plugin\Type\Parser\ParserInterface.
 *
 * @FeedsParser(
 *   id = "parser_with_mapping_form",
 *   title = "Parser with mapping form",
 *   description = @Translation("Parser with form fields on the mapping form."),
 * )
 */
class ParserWithMappingForm extends PluginBase implements ParserInterface, MappingPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    return new ParserResult();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'dummy' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state) {
    $form['dummy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dummy'),
      '#default_value' => $this->configuration['dummy'],
      '#required' => TRUE,
      '#weight' => -50,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('dummy') == 'invalid') {
      $form_state->setErrorByName('dummy', 'Invalid value.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'dummy' => $form_state->getValue('dummy'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return [];
  }

}
