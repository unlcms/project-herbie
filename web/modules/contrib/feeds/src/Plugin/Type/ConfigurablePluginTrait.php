<?php

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait to provide configurable plugin methods.
 */
trait ConfigurablePluginTrait {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

}
