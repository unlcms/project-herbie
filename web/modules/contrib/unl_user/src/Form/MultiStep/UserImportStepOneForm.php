<?php

namespace Drupal\unl_user\Form\MultiStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\unl_user\Form\UserImportForm;

/**
 * Implements an example form.
 */
class UserImportStepOneForm extends UserImportForm {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_user_import_step_one';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //We are just starting out, so delete the store...
    $this->deleteStore();
    
    $form['search'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter your search term'),
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('search')) < 3) {
      $form_state->setErrorByName('search', $this->t('The search term is too short. It must be at least 3 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //note: we could actually fetch the results here, but I'd rather save a query term to the store than a potentially huge result set
    //continue to step two
    $this->store->set('unl_import_data', $form_state->getValue('search'));
    $form_state->setRedirect('unl_user.user_import_step_two');
  }

}
