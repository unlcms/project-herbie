<?php

declare(strict_types=1);

namespace Drupal\media_file_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Media File Delete settings for this site.
 */
class MediaDeleteSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'media_file_delete.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'media_file_delete_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['delete_file_default'] = [
      '#default_value' => $config->get('delete_file_default'),
      '#type' => 'checkbox',
      '#title' => $this->t('Delete files from file system when deleting media entities'),
      '#description' => $this->t('Users will be able to change this value when confirming deletion of media entities unless disabled by the setting below.'),
    ];

    $form['disable_delete_control'] = [
      '#default_value' => $config->get('disable_delete_control'),
      '#type' => 'checkbox',
      '#title' => $this->t('Disable user control of file deletion'),
      '#description' => $this->t('Prevents changing the default value provided by the previous setting.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('delete_file_default', $form_state->getValue('delete_file_default'))
      ->set('disable_delete_control', $form_state->getValue('disable_delete_control'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
