<?php

namespace Drupal\unl_webform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures unl_webform settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_webform_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unl_webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('unl_webform.settings');

    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => 'Webform download key',
      '#description' => 'Append this as a "key" parameter to the end of the unl_webform.submissions.download route to enable downloading of webform results via a URL. To disable, save this field as empty. Download path format: /admin/structure/webform/manage/{webform}/results/download/unl?key={your_key}',
      '#default_value' => $config->get('key'),
    );

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
    $config = $this->config('unl_webform.settings');
    $config->set('key', trim($form_state->getValue('key')));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
