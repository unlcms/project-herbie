<?php

namespace Drupal\unl_user\Form\MultiStep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\unl_user\Form\UserImportForm;
use Drupal\unl_user\Helper;
use Drupal\unl_user\PersonDataQuery;

/**
 * Implements an example form.
 */
class UserImportStepTwoForm extends UserImportForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_user_import_step_two';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //Perform the query
    $search = $this->store->get('unl_import_data');

    $query = new PersonDataQuery();
    $results = $query->search($search);

    if (empty($results)) {
      //No results could be found, so restart the process
      $this->messenger()->addError($this->t('No results could be found for: @search', ['@search' => $search]));
      $form_state->setRedirect('unl_user.user_import');

      $form['actions']['#type'] = 'actions';
      $form['actions']['start_over'] = array(
        '#title' => $this->t('Start Over'),
        '#type' => 'link',
        '#url' => Url::fromRoute('unl_user.user_import')
      );

      return $form; //exit early
    }

    $matches = [];
    foreach ($results as $details) {
      // Generate an affiliations string if user has any affiliations.
      if ($details['data']['unl']['eduPersonAffiliation']) {
        $affiliations = ' (' . implode(', ', $details['data']['unl']['eduPersonAffiliation']) . ')';
      }
      else {
        $affiliations = '';
      }
      $matches[$details['uid']] = $details['data']['unl']['displayName'] . ' (' . $details['data']['unl']['nuid'] . '/' . $details['data']['unl']['unl_uid'] .')' . $affiliations;
    }

    $form['uid'] = array(
      '#type' => 'radios',
      '#title' => sizeof($matches).' people found. Select a person to add to the site:',
      '#required' => true,
      '#options' => $matches,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['start_over'] = array(
      '#title' => $this->t('Start Over'),
      '#type' => 'link',
      '#url' => Url::fromRoute('unl_user.user_import')
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import Selected User'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //The required field should take care of this
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $helper = new Helper();
    $user = $helper->initializeUser($form_state->getValue('uid'));

    $this->messenger()->addStatus($this->t('imported @uid', ['@uid' => $form_state->getValue('uid')]));

    //Redirect to the edit the new user
    $form_state->setRedirect('entity.user.edit_form',array('user' => $user->id()));
  }

}
