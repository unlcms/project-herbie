<?php

namespace Drupal\unl_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\unl_user\PersonDataQuery;

/**
 * Configures unl_user settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_user_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unl_user.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('unl_user.settings');

    $form['uri'] = array(
        '#type' => 'textfield',
        '#title' => 'LDAP URI',
        '#description' => 'ie: ldap://example.com/',
        '#default_value' => $config->get('uri'),
        '#required' => TRUE,
    );
    $form['dn'] = array(
        '#type' => 'textfield',
        '#title' => 'Distinguished Name (DN)',
        '#description' => 'ie: uid=admin,dc=example,dc=com',
        '#default_value' => $config->get('dn'),
        '#required' => TRUE,
    );
    $form['password'] = array(
        '#type' => 'textfield',
        '#title' => 'Password',
        '#default_value' => $config->get('password'),
        '#required' => TRUE,
    );
    $form['username_format'] = array(
      '#type' => 'radios',
      '#title' => 'Username format',
      '#default_value' => $config->get('username_format'),
      '#required' => TRUE,
      '#options' => ['myunl' => 'My.UNL (hhusker1)', 'nuid' => 'NUID (12345678)'],
    );

    // Indicate if LDAP is working or not.
    $query = new PersonDataQuery();
    $result = $query->getUserData(\Drupal::currentUser()->getAccountName());
    $affiliation = \Drupal::service('user.data')->get('unl_user', \Drupal::currentUser()->id(), 'eduPersonPrimaryAffiliation');

    if (!$result || $result['data']['unl']['source'] !== PersonDataQuery::SOURCE_LDAP) {
      $this->messenger()->addWarning($this->t('LDAP is NOT being used. Please ensure credentials are correct. Your primary affiliation is: @affiliation', ['@affiliation' => $affiliation]));
    } else {
      $this->messenger()->addStatus($this->t('LDAP is being used, your primary affiliation is: @affiliation', ['@affiliation' => $affiliation]));
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('unl_user.settings');

    $config->set('uri', $form_state->getValue('uri'));
    $config->set('dn', $form_state->getValue('dn'));
    $config->set('password', $form_state->getValue('password'));
    $config->set('username_format', $form_state->getValue('username_format'));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
