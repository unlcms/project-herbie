<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class of layouts with configurable widths.
 */
abstract class DcfLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $width_classes = array_keys($this->getWidthOptions());
    return parent::defaultConfiguration() + [
      'column_widths' => array_shift($width_classes),
      'title' => '',
      'title_classes' => '',
      'section_package' => '',
      'section_classes' => '',
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

    // Allow editors to select a title for the section.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $configuration['title'],
      '#description' => $this->t('Optional heading for this section.'),
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
          ':input[name="layout_settings[title]"]' => ['filled' => TRUE],
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
      '#description' => $this->t('Package of classes to apply to section.'),
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['column_widths'] = $form_state->getValue('column_widths');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['title_classes'] = $form_state->getValue('title_classes');
    $this->configuration['section_package'] = $form_state->getValue('section_package');
    $this->configuration['section_classes'] = empty($this->configuration['section_package']) ? $form_state->getValue('section_classes') : [];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $configuration = $this->getConfiguration();

    // Add classes to section title.
    $title_classes = (array) $configuration['title_classes'];
    $settings = $build['#settings'];
    $settings['title_classes'] = implode(' ', $title_classes);
    $build['#settings'] = $settings;

    // Add section classes from the package, or custom classes.
    if (!empty($configuration['section_package'])) {
      $config_dcf_classes = \Drupal::config('dcf_classes.classes');
      $section_packages = $config_dcf_classes->get('section_packages');
      $section_classes = $section_packages[$configuration['section_package']];
    }
    else {
      $section_classes = $configuration['section_classes'];
    }
    $settings = $build['#settings'];
    $settings['extra_classes'] = implode(' ', (array) $section_classes);
    $build['#settings'] = $settings;

    // Add default classes.
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
