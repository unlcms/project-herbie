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
      'section_package' => '',
      'section_classes' => '',
      'block_margin' => '',
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

    $heading_classes = $config_dcf_classes->get('heading');
    $options = [];
    foreach ($heading_classes as $class) {
      $options[$class] = $class;
    }
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

    // Allow editors to select a section class package.
    $section_packages = $config_dcf_classes->get('section_packages');
    $options = [];
    foreach ($section_packages as $key => $package) {
      $options[$key] = $key;
    }
    $form['section_package'] = [
      '#type' => 'select',
      '#title' => $this->t('Section style package'),
      '#default_value' => $configuration['section_package'],
      '#options' => $options,
      '#description' => $this->t('Package of classes to apply to the section.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => FALSE,
    ];

    // Allow editors to select html classes using user-friendly term names.
    $section_classes = $config_dcf_classes->get('section');
    $options = [];
    foreach ($section_classes as $class) {
      $options[$class] = $class;
    }

    $form['section_classes'] = [
      '#type' => 'select',
      '#title' => $this->t('Classes'),
      '#default_value' => $configuration['section_classes'],
      '#options' => $options,
      '#description' => $this->t('Wrap the markup for this section with one or more classes.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => TRUE,
      '#states' => [
        'visible' => [
          'select[name="layout_settings[section_package]"]' => ['value' => ''],
        ],
      ],
    ];

    // Allow editors to set the vertical margin between blocks.
    $options = [
      'dcf-mt-1' => 'dcf-mt-1',
      'dcf-mt-2' => 'dcf-mt-2',
      'dcf-mt-3' => 'dcf-mt-3',
      'dcf-mt-4' => 'dcf-mt-4',
      'dcf-mt-5' => 'dcf-mt-5',
      'dcf-mt-6' => 'dcf-mt-6',
      'dcf-mt-7' => 'dcf-mt-7',
      'dcf-mt-8' => 'dcf-mt-8',
      'dcf-mt-9' => 'dcf-mt-9',
      'dcf-mt-10' => 'dcf-mt-10',
    ];

    $form['block_margin'] = [
      '#type' => 'select',
      '#title' => $this->t('Block margin'),
      '#default_value' => $configuration['block_margin'],
      '#options' => $options,
      '#description' => $this->t('The amount of vertical margin between blocks.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
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
    $this->configuration['section_package'] = $form_state->getValue('section_package');
    $this->configuration['section_classes'] = empty($this->configuration['section_package']) ? $form_state->getValue('section_classes') : [];
    $this->configuration['block_margin'] = $form_state->getValue('block_margin');
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

    // Add section classes from the package, or custom classes.
    if (!empty($configuration['section_package'])) {
      $config_dcf_classes = \Drupal::config('dcf_classes.classes');
      $section_packages = $config_dcf_classes->get('section_packages');
      $section_classes = explode(' ', $section_packages[$configuration['section_package']]);
    }
    else {
      $section_classes = $configuration['section_classes'];
    }

    if (!empty($section_classes)) {
      $build['#settings']['section_attributes']->addClass($section_classes);
    }
    if (!empty($configuration['section_element_id'])) {
      $build['#settings']['section_attributes']->setAttribute('id', $configuration['section_element_id']);
    }

    // Add designated margin-top to each block, except the first block in
    // a region. Margin-top is used instead of margin-bottom because it's
    // possible to know the first item during the loop; however, it's not
    // possible to know the last item until the loop has completed.
    if (!empty($configuration['block_margin'])) {
      foreach ($build as $region_id => $region) {
        if (substr($region_id, 0, 1) !== "#") {
          $block_count = 0;
          foreach ($region as $block_id => $block) {
            if (substr($block_id, 0, 1) !== "#") {
              if ($block_count > 0) {
                $build[$region_id][$block_id]['#attributes']['class'][] = $configuration['block_margin'];
              }
              $block_count++;
            }
          }
        }
      }
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
   * Generate column classes form elements.
   *
   * @param int $column_count
   *   The number of columns in the layout.
   *
   * @return array
   *   A partial form array.
   */
  protected function columnClassFormElements(int $column_count) {
    // Allow editors to select html classes using user-friendly term names.
    $column_classes = \Drupal::config('dcf_classes.classes')->get('column');
    $options = [];
    foreach ($column_classes as $class) {
      $options[$class] = $class;
    }
    $form['column_classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Column Classes'),
      '#weight' => 50,
    ];

    for ($i = 1; $i <= $column_count; $i++) {
      $form['column_classes'][$i] = [
        '#type' => 'select',
        '#title' => $this->t('Column @i classes', ['@i' => $i]),
        '#default_value' => $this->configuration['column_classes']['col_' . $i],
        '#options' => $options,
        '#description' => $this->t('Select classes for the column.'),
        '#empty_option' => $this->t('- None -'),
        '#empty_value' => '',
        '#multiple' => TRUE,
      ];
    }

    return $form;
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
