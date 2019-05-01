<?php

namespace Drupal\ckeditor_dcf\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "CKEditor DCF Base" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ckeditor_dcf_base",
 *   label = @Translation("Digital Campus Framework")
 * )
 */
class DcfBasePlugin extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    // Each plugin provided by this module should be provided as an option.
    $options = [
      'ckeditor_dcf_table' => t('DCF Table'),
    ];

    $config = ['enabled_plugins' => ''];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['ckeditor_dcf_base'])) {
      $config = $settings['plugins']['ckeditor_dcf_base'];
    }

    // Load Editor settings.
    $settings = $editor->getSettings();

    $form['enabled_plugins'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Enabled DCF Plugins'),
      '#default_value' => $config['enabled_plugins'],
      '#description' => $this->t('CKEditor DCF plugins that should be enabled for this editor.'),
      '#element_validate' => [
        [$this, 'validateEnabledPlugins'],
      ],
    ];

    return $form;
  }

  /**
   * Custom validator for the "enabled_plugins" element in settingsForm().
   */
  public function validateEnabledPlugins(array $element, FormStateInterface &$form_state) {
    // Convert submitted value into an array. Return if empty.
    $config_value = $element['#value'];
    if (empty($config_value)) {
      return;
    }

    // Drupal schema won't allow a value to be multiple types, so non-strings
    // need to be cast as strings prior to saving.
    foreach ($config_value as $k => $v) {
      if (!is_string($v)) {
        $form_state->setValue([
          'editor',
          'settings',
          'plugins',
          'ckeditor_dcf_base',
          'enabled_plugins',
          $k,
        ], (string) $v);
      }
    }
  }

}
