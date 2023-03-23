<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Template\Attribute;

/**
 * Base class of layouts with configurable widths.
 */
abstract class DcfLayoutBase extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $width_classes = array_keys($this->getWidthOptions());
    return parent::defaultConfiguration() + [
      'column_widths' => array_shift($width_classes),
      'title' => '',
      'title_classes' => '',
      'title_display' => FALSE,
      'section_element_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $config_dcf_classes = \Drupal::config('dcf_classes.classes');

    // Allow editors to select the column widths for the section.
    $form['column_widths'] = [
      '#type' => 'select',
      '#title' => $this->t('Column widths'),
      '#default_value' => $this->configuration['column_widths'],
      '#options' => $this->getWidthOptions(),
      '#description' => $this->t('Choose the column widths for this layout.'),
    ];

    // Require editors to set a title for the section.
    // This is also stored as the 'Administrative label' in D8.8+.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $configuration['title'],
      '#description' => $this->t('Optional heading for this section.'),
      '#required' => TRUE,
    ];

    // Allow editors to display section title on render.
    $form['title_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => $configuration['title_display'],
    ];

    $options = [
      'dcf-regular' => 'dcf-regular',
      'dcf-txt-h1' => 'dcf-txt-h1',
      'dcf-txt-h2' => 'dcf-txt-h2',
      'dcf-txt-h3' => 'dcf-txt-h3',
      'dcf-txt-h4' => 'dcf-txt-h4',
      'dcf-txt-h5' => 'dcf-txt-h5',
      'dcf-txt-h6' => 'dcf-txt-h6',
      'dcf-sr-only' => 'dcf-sr-only',
      'dcf-d-none@print' => 'dcf-d-none@print',
      'dcf-capitalize' => 'dcf-capitalize',
      'dcf-lowercase' => 'dcf-lowercase',
      'dcf-uppercase' => 'dcf-uppercase',
    ];
    $form['title_classes'] = [
      '#type' => 'select',
      '#title' => $this->t('Title classes'),
      '#default_value' => $configuration['title_classes'],
      '#options' => $options,
      '#description' => $this->t('Select classes for the title.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[title_display]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#weight' => 51,
    ];

    $form['advanced']['section_element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section element ID'),
      '#description' => $this->t("The ID attribute on the layout's &lt;div&gt; element"),
      '#default_value' => $configuration['section_element_id'],
    ];

    // Needed until https://www.drupal.org/project/drupal/issues/3080698
    // is fixed.
    $form['#attached']['library'][] = 'dcf_layouts/drupal.dialog.off_canvas';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['advanced']['section_element_id'] != Html::cleanCssIdentifier($values['advanced']['section_element_id'])) {
      $form_state->setError($form['advanced']['section_element_id'], $this->t('Element ID must be a valid CSS ID'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['label'] = $form_state->getValue('title');
    $this->configuration['column_widths'] = $form_state->getValue('column_widths');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['title_display'] = (boolean) $form_state->getValue('title_display');
    $this->configuration['title_classes'] = $form_state->getValue('title_classes');
    $this->configuration['section_element_id'] = $form_state->getValue('advanced')['section_element_id'];

    $column_count = $form_state->get('column_count');
    for ($i = 1; $i <= $column_count; $i++) {
      $this->configuration['column_classes']['col_' . $i] = $form_state->getValue('column_classes')[$i];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $configuration = $this->getConfiguration();

    // Initialize attributes.
    $build['#settings']['section_attributes'] = new Attribute();
    $build['#settings']['title_attributes'] = new Attribute();

    // Don't display title unless 'title_display' is checked.
    if (isset($configuration['title_display']) && $configuration['title_display'] == FALSE) {
      unset($build['#settings']['title']);
    }

    // Add classes to section title.
    if (!empty($configuration['title_classes'])) {
      $build['#settings']['title_attributes']->addClass($configuration['title_classes']);
    }

    if (!empty($configuration['section_element_id'])) {
      $build['#settings']['section_attributes']->setAttribute('id', $configuration['section_element_id']);
    }

    // Add layout default classes.
    if (!isset($build['#attributes']['class'])) {
      $build['#attributes']['class'] = [];
    }
    $build['#attributes']['class'][] = 'layout';
    $build['#attributes']['class'][] = $this->getPluginDefinition()->getTemplate();

    return $build;
  }

  /**
   * Gets the width options for the configuration form.
   *
   * The first option will be used as the default 'column_widths' configuration
   * value.
   *
   * @return string[]
   *   The width options array where the keys are strings that will be added to
   *   the CSS classes and the values are the human readable labels.
   */
  abstract protected function getWidthOptions();

}
