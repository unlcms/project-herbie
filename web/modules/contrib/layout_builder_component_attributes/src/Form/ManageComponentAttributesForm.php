<?php

namespace Drupal\layout_builder_component_attributes\Form;

use CssLint\Linter;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for managing block attributes.
 */
class ManageComponentAttributesForm extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The component uuid.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The Layout Tempstore.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstore;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new ManageComponentAttributesForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration object factory.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, ConfigFactory $config_factory) {
    $this->layoutTempstore = $layout_tempstore_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_manage_attributes_form';
  }

  /**
   * Builds the attributes form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The original delta of the section.
   * @param string $uuid
   *   The UUID of the block being updated.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL) {
    $parameters = array_slice(func_get_args(), 2);
    foreach ($parameters as $parameter) {
      if (is_null($parameter)) {
        throw new \InvalidArgumentException('ManageComponentAttributesForm requires all parameters.');
      }
    }

    $config = $this->configFactory->get('layout_builder_component_attributes.settings')->get();

    // Determine which categories, if any, are empty (i.e. no
    // allowed attributes).
    $categories = [
      'allowed_block_attributes',
      'allowed_block_title_attributes',
      'allowed_block_content_attributes',
    ];
    $empty_categories = [];
    foreach ($categories as $category) {
      $empty_categories[$category] = TRUE;
      foreach ($config[$category] as $value) {
        if ($value) {
          $empty_categories[$category] = FALSE;
          break;
        }
      }
    }

    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;

    $section = $section_storage->getSection($delta);
    $component = $section->getComponent($uuid);
    $component_attributes = $component->get('component_attributes');

    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockUpdateHighlightId($uuid);

    $form_partial_id = [
      '#type' => 'textfield',
      '#title' => 'ID',
      '#description' => $this->t('An HTML identifier unique to the page.'),
    ];
    $form_partial_class = [
      '#type' => 'textfield',
      '#title' => 'Class(es)',
      '#description' => $this->t('Classes to be applied. Multiple classes should be separated by a space.'),
    ];
    $form_partial_style = [
      '#type' => 'textfield',
      '#title' => 'Style',
      '#description' => $this->t('Inline CSS styles. <em>In general, inline CSS styles should be avoided.</em>'),
    ];
    $form_partial_data = [
      '#type' => 'textarea',
      '#title' => 'Data-* attributes',
      '#description' => $this->t('Custom attributes, which are available to both CSS and JS.<br><br>Each attribute should be entered on its own line with a pipe (|) separating its name and its optional value:<br>data-test|example-value<br>data-attribute-with-no-value'),
    ];

    if (!$empty_categories['allowed_block_attributes']) {
      $form['block_attributes'] = [
        '#type' => 'details',
        '#title' => $this->t('Block attributes'),
      ];
      $form['block_attributes']['intro'] = [
        '#markup' => $this->t('<p>Manage attributes on the block wrapper (outer) element</p>'),
      ];
      if ($config['allowed_block_attributes']['id']) {
        $form['block_attributes']['id'] = $form_partial_id + [
          '#default_value' => $component_attributes['block_attributes']['id'] ?? '',
        ];
      }
      if ($config['allowed_block_attributes']['class']) {
        $form['block_attributes']['class'] = $form_partial_class + [
          '#default_value' => $component_attributes['block_attributes']['class'] ?? '',
        ];
      }
      if ($config['allowed_block_attributes']['style']) {
        $form['block_attributes']['style'] = $form_partial_style + [
          '#default_value' => $component_attributes['block_attributes']['style'] ?? '',
        ];
      }
      if ($config['allowed_block_attributes']['data']) {
        $form['block_attributes']['data'] = $form_partial_data + [
          '#default_value' => $component_attributes['block_attributes']['data'] ?? '',
        ];
      }
    }

    if (!$empty_categories['allowed_block_title_attributes']) {
      $form['block_title_attributes'] = [
        '#type' => 'details',
        '#title' => $this->t('Block title attributes'),
      ];
      $form['block_title_attributes']['intro'] = [
        '#markup' => $this->t('<p>Manage attributes on the block title element</p>'),
      ];
      if ($config['allowed_block_title_attributes']['id']) {
        $form['block_title_attributes']['id'] = $form_partial_id + [
          '#default_value' => $component_attributes['block_title_attributes']['id'] ?? '',
        ];
      }
      if ($config['allowed_block_title_attributes']['class']) {
        $form['block_title_attributes']['class'] = $form_partial_class + [
          '#default_value' => $component_attributes['block_title_attributes']['class'] ?? '',
        ];
      }
      if ($config['allowed_block_title_attributes']['style']) {
        $form['block_title_attributes']['style'] = $form_partial_style + [
          '#default_value' => $component_attributes['block_title_attributes']['style'] ?? '',
        ];
      }
      if ($config['allowed_block_title_attributes']['data']) {
        $form['block_title_attributes']['data'] = $form_partial_data + [
          '#default_value' => $component_attributes['block_title_attributes']['data'] ?? '',
        ];
      }
    }

    if (!$empty_categories['allowed_block_content_attributes']) {
      $form['block_content_attributes'] = [
        '#type' => 'details',
        '#title' => $this->t('Block content attributes'),
      ];
      $form['block_content_attributes']['intro'] = [
        '#markup' => $this->t('<p>Manage attributes on the block content (inner) element</p>'),
      ];
      if ($config['allowed_block_content_attributes']['id']) {
        $form['block_content_attributes']['id'] = $form_partial_id + [
          '#default_value' => $component_attributes['block_content_attributes']['id'] ?? '',
        ];
      }
      if ($config['allowed_block_content_attributes']['class']) {
        $form['block_content_attributes']['class'] = $form_partial_class + [
          '#default_value' => $component_attributes['block_content_attributes']['class'] ?? '',
        ];
      }
      if ($config['allowed_block_content_attributes']['style']) {
        $form['block_content_attributes']['style'] = $form_partial_style + [
          '#default_value' => $component_attributes['block_content_attributes']['style'] ?? '',
        ];
      }
      if ($config['allowed_block_content_attributes']['data']) {
        $form['block_content_attributes']['data'] = $form_partial_data + [
          '#default_value' => $component_attributes['block_content_attributes']['data'] ?? '',
        ];
      }
    }

    $form['#tree'] = TRUE;

    // Workaround for core bug:
    // https://www.drupal.org/project/drupal/issues/2897377.
    $form['#id'] = Html::cleanCssIdentifier($this->getFormId());

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#button_type' => 'primary',
    ];

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate block attributes.
    if (isset($values['block_attributes']['id']) && !$this->validateCssClass($values['block_attributes']['id'])) {
      $form_state->setError($form['block_attributes']['id'], $this->t('Element ID must be a valid CSS ID'));
    }

    if (isset($values['block_attributes']['class'])) {
      $classes = explode(' ', $values['block_attributes']['class']);
      foreach ($classes as $class) {
        if (!$this->validateCssClass($class)) {
          $form_state->setError($form['block_attributes']['class'], $this->t('Classes must be valid CSS classes'));
          break;
        }
      }
    }

    if (isset($values['block_attributes']['style'])) {
      $cssLinter = new Linter();
      $style_validity = $cssLinter->lintString('.selector {' . $values['block_attributes']['style'] . '}');
      if (!$style_validity) {
        $form_state->setError($form['block_attributes']['style'], $this->t('Inline styles must be valid CSS'));
      }
    }

    if (isset($values['block_attributes']['data'])) {
      $data_attributes = preg_split('/\R/', $values['block_attributes']['data']);
      foreach ($data_attributes as $data_attribute) {
        if (empty($data_attribute)) {
          break;
        }
        $data_attribute = explode('|', $data_attribute);
        if (substr($data_attribute[0], 0, 5) !== "data-") {
          $form_state->setError($form['block_attributes']['data'], $this->t('Data attributes must being with "data-"'));
        }
      }
    }

    // Validate block title attributes.
    if (isset($values['block_title_attributes']['id']) && !$this->validateCssClass($values['block_title_attributes']['id'])) {
      $form_state->setError($form['block_title_attributes']['id'], $this->t('Element ID must be a valid CSS ID'));
    }

    if (isset($values['block_title_attributes']['class'])) {
      $classes = explode(' ', $values['block_title_attributes']['class']);
      foreach ($classes as $class) {
        if (!$this->validateCssClass($class)) {
          $form_state->setError($form['block_title_attributes']['class'], $this->t('Classes must be valid CSS classes'));
          break;
        }
      }
    }

    if (isset($values['block_title_attributes']['style'])) {
      $cssLinter = new Linter();
      $style_validity = $cssLinter->lintString('.selector {' . $values['block_title_attributes']['style'] . '}');
      if (!$style_validity) {
        $form_state->setError($form['block_title_attributes']['style'], $this->t('Inline styles must be valid CSS'));
      }
    }

    if (isset($values['block_title_attributes']['data'])) {
      $data_attributes = preg_split('/\R/', $values['block_title_attributes']['data']);
      foreach ($data_attributes as $data_attribute) {
        if (empty($data_attribute)) {
          break;
        }
        $data_attribute = explode('|', $data_attribute);
        if (substr($data_attribute[0], 0, 5) !== "data-") {
          $form_state->setError($form['block_title_attributes']['data'], $this->t('Data attributes must being with "data-"'));
        }
      }
    }

    // Validate block content attributes.
    if (isset($values['block_content_attributes']['id']) && !$this->validateCssClass($values['block_content_attributes']['id'])) {
      $form_state->setError($form['block_content_attributes']['id'], $this->t('Element ID must be a valid CSS ID'));
    }

    if (isset($values['block_content_attributes']['class'])) {
      $classes = explode(' ', $values['block_content_attributes']['class']);
      foreach ($classes as $class) {
        if (!$this->validateCssClass($class)) {
          $form_state->setError($form['block_content_attributes']['class'], $this->t('Classes must be valid CSS classes'));
          break;
        }
      }
    }

    if (isset($values['block_content_attributes']['style'])) {
      $cssLinter = new Linter();
      $style_validity = $cssLinter->lintString('.selector {' . $values['block_content_attributes']['style'] . '}');
      if (!$style_validity) {
        $form_state->setError($form['block_content_attributes']['style'], $this->t('Inline styles must be valid CSS'));
      }
    }

    if (isset($values['block_content_attributes']['data'])) {
      $data_attributes = preg_split('/\R/', $values['block_content_attributes']['data']);
      foreach ($data_attributes as $data_attribute) {
        if (empty($data_attribute)) {
          break;
        }
        $data_attribute = explode('|', $data_attribute);
        if (substr($data_attribute[0], 0, 5) !== "data-") {
          $form_state->setError($form['block_content_attributes']['data'], $this->t('Data attributes must being with "data-"'));
        }
      }
    }
  }

  /**
   * Helper function to validate CSS classes.
   *
   * @param string $value
   *   The CSS class to be validated.
   *
   * @return bool
   *   Whether or not the class is valid.
   */
  protected function validateCssClass($value) {
    if ($value == Html::cleanCssIdentifier($value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delta = $this->getSelectedDelta($form_state);
    $section = $this->sectionStorage->getSection($delta);

    $values = $form_state->getValues();

    $additional_settings = [
      'block_attributes' => [
        'id' => $values['block_attributes']['id'] ?? '',
        'class' => $values['block_attributes']['class'] ?? '',
        'style' => $values['block_attributes']['style'] ?? '',
        'data' => $values['block_attributes']['data'] ?? '',
      ],
      'block_title_attributes' => [
        'id' => $values['block_title_attributes']['id'] ?? '',
        'class' => $values['block_title_attributes']['class'] ?? '',
        'style' => $values['block_title_attributes']['style'] ?? '',
        'data' => $values['block_title_attributes']['data'] ?? '',
      ],
      'block_content_attributes' => [
        'id' => $values['block_content_attributes']['id'] ?? '',
        'class' => $values['block_content_attributes']['class'] ?? '',
        'style' => $values['block_content_attributes']['style'] ?? '',
        'data' => $values['block_content_attributes']['data'] ?? '',
      ],
    ];

    // Store configuration in layout_builder.component.additional.
    // Switch to third-party settings when
    // https://www.drupal.org/project/drupal/issues/3015152 is committed.
    $section->getComponent($this->uuid)->set('component_attributes', $additional_settings);

    $this->layoutTempstore->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Gets the selected delta.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return int
   *   The section delta.
   */
  protected function getSelectedDelta(FormStateInterface $form_state) {
    if ($form_state->hasValue('region')) {
      return (int) explode(':', $form_state->getValue('region'))[0];
    }
    return (int) $this->delta;
  }

}
