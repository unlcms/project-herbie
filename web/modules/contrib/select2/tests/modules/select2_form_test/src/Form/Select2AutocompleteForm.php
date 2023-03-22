<?php

namespace Drupal\select2_form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Test form to test the select2 element.
 *
 * @internal
 */
class Select2AutocompleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_test_select2_autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, bool $customize = FALSE): array {

    $form['select2_autocomplete'] = [
      '#type' => 'select2',
      '#title' => 'Autocomplete',
      '#autocomplete' => TRUE,
      '#target_type' => 'entity_test_mulrevpub',
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => 'Submit'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
