<?php

namespace Drupal\layout_builder_component_attributes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Global settings form.
 */
class GlobalSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'layout_builder_component_attributes.settings';

  /**
   * Configuration categories.
   *
   * @var array
   */
  protected $categories = [
    'allowed_block_attributes',
    'allowed_block_title_attributes',
    'allowed_block_content_attributes',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_component_attributes_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS)->get();

    // Convert the true/false values back into a format FAPI expects.
    // attribute => attribute (for true).
    // attribute => 0 (for false).
    foreach ($config as $category => $cat_config) {
      if (in_array($category, $this->categories)) {
        foreach ($cat_config as $attribute => $value) {
          $config[$category][$attribute] = ($value) ? $attribute : 0;
        }
      }
    }

    $options = [
      'id' => $this->t('ID'),
      'class' => $this->t('Class(es)'),
      'style' => $this->t('Inline CSS styles'),
      'data' => $this->t('Custom data-* attributes'),
    ];

    $form['intro'] = [
      '#markup' => $this->t('<p>Attributes can be added to 1) the block (outer) element, 2) the block title, and 3) the block content (inner) element. Control which attributes are made available to content editors below:</p>'),
    ];

    $form['allowed_block_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed block attributes'),
      '#options' => $options,
      '#default_value' => $config['allowed_block_attributes'],
    ];

    $form['allowed_block_title_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed block title attributes'),
      '#options' => $options,
      '#default_value' => $config['allowed_block_title_attributes'],
    ];

    $form['allowed_block_content_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed block content attributes'),
      '#options' => $options,
      '#default_value' => $config['allowed_block_content_attributes'],
      '#description' => $this->t('In order for attributes to be rendered on the the block content (inner) element, the active front-end theme must support <code>content_attributes</code> in its block.html.twig file. See README.md for more information.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(static::SETTINGS);

    // Loop through configuration categories.
    foreach ($this->categories as $category) {
      $cat_config = $form_state->getValue($category);

      // Convert the FAPI values into booleans for config storage.
      foreach ($cat_config as $attribute => $value) {
        $cat_config[$attribute] = ($value) ? TRUE : FALSE;
      }
      $config->set($category, $cat_config);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
