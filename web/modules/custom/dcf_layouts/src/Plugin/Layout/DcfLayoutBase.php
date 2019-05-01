<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class of layouts with configurable widths.
 */
abstract class DcfLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The custom class vocabulary, used for a better UX for editors.
   *
   * Editors will select a user-friendly term name when adding the layout, and
   * behind the scenes that term contains a field with the list of actual
   * classes that will be added to the layout.
   *
   * @var string
   *   The machine name of the class vocabulary.
   */
  protected $packageVid;

  /**
   * The custom class vocabulary, used for a better UX for editors.
   *
   * Editors will select a user-friendly term name when adding the layout, and
   * behind the scenes that term contains a field with the list of actual
   * classes that will be added to the layout.
   *
   * @var string
   *   The machine name of the class vocabulary.
   */
  protected $classVid;

  /**
   * The custom title class vocabulary, used for a better UX for editors.
   *
   * Editors will select a user-friendly term name when adding the layout, and
   * behind the scenes that term contains a field with the list of actual
   * classes that will be added to the layout.
   *
   * @var string
   *   The machine name of the title class vocabulary.
   */
  protected $titleClassVid;

  /**
   * The field on the vocabulary term that contains actual classes.
   *
   * @var string
   *   The machine name of the vocabulary class field.
   */
  protected $classField;

  /**
   * Entity Type Manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Assumption is that this vocabulary exists, and has s_t_class field.
    // @TODO: move to a configuration form.
    $this->packageVid = 'dcf_band_packages';
    $this->classVid = 'dcf_band_classes';
    $this->titleClassVid = 'dcf_title_classes';
    $this->classField = 's_t_class';
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
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
      'title_terms' => '',
      'package' => '',
      'terms' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

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

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($this->getTitleVid());
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['title_terms'] = [
      '#type' => 'select',
      '#title' => $this->t('Title classes'),
      '#default_value' => $configuration['title_terms'],
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

    // Allow editors to select a band class package.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($this->getPackageVid());
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['package'] = [
      '#type' => 'select',
      '#title' => $this->t('Section style package'),
      '#default_value' => $configuration['package'],
      '#options' => $options,
      '#description' => $this->t('Package of classes to apply to section.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => FALSE,
    ];

    // Allow editors to select html classes using user-friendly term names.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($this->getClassVid());
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $form['terms'] = [
      '#type' => 'select',
      '#title' => $this->t('Classes'),
      '#default_value' => $configuration['terms'],
      '#options' => $options,
      '#description' => $this->t('Wrap the markup for this section with one or more classes.'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#multiple' => TRUE,
      '#states' => [
        'visible' => [
          'select[name="layout_settings[package]"]' => ['value' => ''],
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
    $this->configuration['title_terms'] = $form_state->getValue('title_terms');
    $this->configuration['package'] = $form_state->getValue('package');
    $this->configuration['terms'] = empty($this->configuration['package']) ? $form_state->getValue('terms') : [];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    // Retrieve the vocabulary term info.
    $configuration = $this->getConfiguration();
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Add vocabulary classes to section title.
    $more_classes = [];
    $terms = (array) $configuration['title_terms'];
    foreach ($terms as $term_id) {
      if ($term = $storage->load($term_id)) {
        $value = $term->{$this->classField}->value;
        $more_classes[] = $value;
      }
    }
    if (!empty($more_classes)) {
      $settings = $build['#settings'];
      $settings['title_classes'] = implode(' ', $more_classes);
      $build['#settings'] = $settings;
    }

    // Add band/section classes from the package, or custom classes.
    $more_classes = [];
    $terms = (array) (!empty($configuration['package']) ? $configuration['package'] : $configuration['terms']);
    foreach ($terms as $term_id) {
      if ($term = $storage->load($term_id)) {
        $value = $term->{$this->classField}->value;
        $more_classes[] = $value;
      }
    }
    if (!empty($more_classes)) {
      $settings = $build['#settings'];
      $settings['extra_classes'] = implode(' ', $more_classes);
      $build['#settings'] = $settings;
    }

    // Add default classes.
    if (!isset($build['#attributes']['class'])) {
      $build['#attributes']['class'] = [];
    }
    $build['#attributes']['class'][] = 'layout';
    $build['#attributes']['class'][] = $this->getPluginDefinition()->getTemplate();

    return $build;
  }

  /**
   * The custom class package vocabulary.
   */
  public function getPackageVid() {
    return $this->packageVid;
  }

  /**
   * The custom class vocabulary.
   */
  public function getClassVid() {
    return $this->classVid;
  }

  /**
   * The custom class vocabulary.
   */
  public function getTitleVid() {
    return $this->titleClassVid;
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
