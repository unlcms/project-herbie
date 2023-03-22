<?php

namespace Drupal\feeds\Plugin\Type;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for Feeds plugins that hook into the mapping form.
 */
interface MappingPluginFormInterface {

  /**
   * Alter mapping form.
   *
   * @param array $form
   *   The mapping form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the mapping form.
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state);

  /**
   * Validate handler for the mapping form.
   *
   * @param array $form
   *   The mapping form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the mapping form.
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state);

  /**
   * Submit handler for the mapping form.
   *
   * @param array $form
   *   The mapping form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the mapping form.
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state);

}
