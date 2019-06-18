<?php

namespace Drupal\unl_utility;

use Drupal\Core\Form\FormStateInterface;

/**
 * Utility methods.
 */
trait UNLUtilityTrait {

  /**
   * Clears a given error on a FormState object.
   *
   * FormStateInterface provides methods to set individual errors and
   * to clear all errors; however, it does not provide a method to
   * clear an individual error. This method provides that missing
   * functionality.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An interface for an object containing the current state of a form.
   * @param string $error_name
   *   The name of the error being cleared.
   */
  public static function formStateClearError(FormStateInterface &$form_state, string $error_name) {
    $form_errors = $form_state->getErrors();
    $form_state->clearErrors();
    if (isset($form_errors[$error_name])) {
      unset($form_errors[$error_name]);
    }
    foreach ($form_errors as $name => $error_message) {
      $form_state->setErrorByName($name, $error_message);
    }
  }

}
