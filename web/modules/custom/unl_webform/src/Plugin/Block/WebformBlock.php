<?php

namespace Drupal\unl_webform\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\Block\WebformBlock as ExtendedWebformBlock;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "unl_webform_block",
 *   admin_label = @Translation("Webform"),
 *   category = @Translation("Forms")
 * )
 */
class WebformBlock extends ExtendedWebformBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Remove settings fieldset.
    unset($form['settings']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['webform_id'] = $values['webform_id'];
  }

}
