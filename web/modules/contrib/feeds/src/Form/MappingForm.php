<?php

namespace Drupal\feeds\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\feeds\Exception\MissingTargetException;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\MissingTargetDefinition;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for mapping settings.
 */
class MappingForm extends FormBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $feedTypeStorage;

  /**
   * The mappings for this feed type.
   *
   * @var array
   */
  protected $mappings;

  /**
   * Constructs a new MappingForm object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $feed_type_storage
   *   The feed type storage.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $custom_source_plugin_manager
   *   The custom source plugin manager.
   */
  public function __construct(ConfigEntityStorageInterface $feed_type_storage, PluginManagerInterface $custom_source_plugin_manager) {
    $this->feedTypeStorage = $feed_type_storage;
    $this->customSourcePluginManager = $custom_source_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('feeds_feed_type'),
      $container->get('plugin.manager.feeds.custom_source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FeedTypeInterface $feeds_feed_type = NULL) {
    $feed_type = $this->feedType = $feeds_feed_type;
    $this->targets = $targets = $feed_type->getMappingTargets();

    // Determine available mapping sources.
    $this->sourceOptions = [];
    foreach ($this->getMappingSourcesPerType() as $type => $sources) {
      foreach ($sources as $key => $source) {
        $this->sourceOptions[$type][$key] = $source['label'];
        // Add machine name between parentheses to the option label in case it's
        // not equal to the source label.
        if (isset($source['machine_name']) && $source['label'] != $source['machine_name']) {
          $this->sourceOptions[$type][$key] .= ' (' . $source['machine_name'] . ')';
        }
      }
    }
    // Sort sources on label within each group.
    foreach ($this->sourceOptions as $type => $values) {
      $this->sourceOptions[$type] = $this->sortOptions($values);
    }

    // Determine available mapping targets.
    $target_options = [];
    foreach ($targets as $key => $target) {
      $target_options[$key] = $target->getLabel() . ' (' . $key . ')';
    }
    // Sort targets on label.
    $target_options = $this->sortOptions($target_options);

    // Check if two mappings are exactly the same. If so, display a warning
    // about that to the user.
    $this->checkDuplicateMappings($feed_type, $target_options);

    if ($form_state->getValues()) {
      $this->processFormState($form, $form_state);

      $triggering_element = $form_state->getTriggeringElement() + ['#op' => ''];

      switch ($triggering_element['#op']) {
        case 'cancel':
        case 'configure':
          // These don't need a configuration message.
          break;

        default:
          $this->messenger()->addWarning($this->t('Your changes will not be saved until you click the <em>Save</em> button at the bottom of the page.'));
          break;
      }
    }

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="feeds-mapping-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'feeds/feeds';

    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Target'),
        $this->t('Summary'),
        $this->t('Configure'),
        $this->t('Unique'),
        $this->t('Remove'),
      ],
      '#sticky' => TRUE,
    ];

    foreach ($feed_type->getMappings() as $delta => $mapping) {
      $table[$delta] = $this->buildRow($form, $form_state, $mapping, $delta);
    }

    $table['add']['source']['#markup'] = '';

    $table['add']['target'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a target'),
      '#title_display' => 'invisible',
      '#options' => $target_options,
      '#empty_option' => $this->t('- Select a target -'),
      '#parents' => ['add_target'],
      '#default_value' => NULL,
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'none',
        'progress' => 'none',
      ],
    ];

    $table['add']['summary']['#markup'] = '';
    $table['add']['configure']['#markup'] = '';
    $table['add']['unique']['#markup'] = '';
    $table['add']['remove']['#markup'] = '';

    $form['mappings'] = $table;

    // Legend explaining source and target elements.
    $form['legendset'] = $this->buildLegend($form, $form_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Allow plugins to hook into the mapping form.
    foreach ($feed_type->getPlugins() as $plugin) {
      if ($plugin instanceof MappingPluginFormInterface) {
        $plugin->mappingFormAlter($form, $form_state);
      }
    }

    return $form;
  }

  /**
   * Builds a single mapping row.
   *
   * @param array $form
   *   The complete mapping form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   * @param array $mapping
   *   A single configured mapper, which is expected to consist of the
   *   following:
   *   - map
   *     An array of target subfield => source field.
   *   - target
   *     The name of the target plugin.
   *   - unique
   *     (optional) An array of subfield => enabled as unique.
   *   - settings
   *     (optional) An array of settings for the target.
   * @param int $delta
   *   The index number of the mapping.
   *
   * @return array
   *   The form structure for a single mapping row.
   */
  protected function buildRow(array $form, FormStateInterface $form_state, array $mapping, $delta) {
    try {
      /** @var \Drupal\feeds\Plugin\Type\TargetInterface $plugin */
      $plugin = $this->feedType->getTargetPlugin($delta);
    }
    catch (MissingTargetException $e) {
      // The target plugin is missing!
      $this->messenger()->addWarning($e->getMessage());
      watchdog_exception('feeds', $e);
      $plugin = NULL;
    }

    // Check if the target exists.
    if (!empty($this->targets[$mapping['target']])) {
      /** @var \Drupal\feeds\TargetDefinitionInterface $target_definition */
      $target_definition = $this->targets[$mapping['target']];
    }
    else {
      // The target is missing! Create a placeholder target definition, so that
      // the mapping row is still being displayed.
      $target_definition = MissingTargetDefinition::create();
    }

    $ajax_delta = -1;
    $triggering_element = (array) $form_state->getTriggeringElement() + ['#op' => ''];
    if ($triggering_element['#op'] === 'configure') {
      $ajax_delta = $form_state->getTriggeringElement()['#delta'];
    }

    $row = ['#attributes' => ['class' => ['draggable', 'tabledrag-leaf']]];
    $row['map'] = ['#type' => 'container'];
    $row['targets'] = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => ['class' => ['target']],
    ];

    if ($target_definition instanceof MissingTargetDefinition) {
      $row['#attributes']['class'][] = 'missing-target';
      $row['#attributes']['class'][] = 'color-error';
    }

    foreach ($mapping['map'] as $column => $source) {
      if (!$target_definition->hasProperty($column)) {
        unset($mapping['map'][$column]);
        continue;
      }
      $row['map'][$column] = [
        'select' => [
          '#type' => 'select',
          '#options' => $this->sourceOptions,
          '#default_value' => $source,
          '#empty_option' => $this->t('- Select a source -'),
          '#attributes' => ['class' => ['feeds-table-select-list']],
        ],
      ];
      $this->buildCustomSourceForms($row['map'][$column], $form_state, $delta, $column);

      $label = Html::escape($target_definition->getLabel() . ' (' . $mapping['target'] . ')');

      if (count($mapping['map']) > 1) {
        $desc = $target_definition->getPropertyLabel($column);
      }
      else {
        $desc = $target_definition->getDescription();
      }
      if ($desc) {
        $label .= ': ' . $desc;
      }
      $row['targets']['#items'][] = $label;
    }

    $default_button = [
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'fade',
        'progress' => 'none',
      ],
      '#delta' => $delta,
    ];

    $row['settings']['#markup'] = '';
    $row['configure']['#markup'] = '';
    if ($plugin && $this->pluginHasSettingsForm($plugin, $form_state)) {
      if ($delta == $ajax_delta) {
        $row['settings'] = $plugin->buildConfigurationForm([], $form_state);
        $row['settings']['actions'] = [
          '#type' => 'actions',
          'save_settings' => $default_button + [
            '#type' => 'submit',
            '#button_type' => 'primary',
            '#value' => $this->t('Update'),
            '#op' => 'update',
            '#name' => 'target-save-' . $delta,
          ],
          'cancel_settings' => $default_button + [
            '#type' => 'submit',
            '#value' => $this->t('Cancel'),
            '#op' => 'cancel',
            '#name' => 'target-cancel-' . $delta,
            '#limit_validation_errors' => [[]],
          ],
        ];
        $row['#attributes']['class'][] = 'feeds-mapping-settings-editing';
      }
      else {
        $row['settings'] = [
          '#parents' => ['config_summary', $delta],
        ] + $this->buildSummary($plugin);
        $row['configure'] = $default_button + [
          '#type' => 'image_button',
          '#op' => 'configure',
          '#name' => 'target-settings-' . $delta,
          '#src' => 'core/misc/icons/787878/cog.svg',
        ];
      }
    }
    elseif ($plugin instanceof ConfigurableTargetInterface) {
      $summary = $this->buildSummary($plugin);
      if (!empty($summary)) {
        $row['settings'] = [
          '#parents' => ['config_summary', $delta],
        ] + $this->buildSummary($plugin);
      }
    }

    $mappings = $this->feedType->getMappings();

    foreach ($mapping['map'] as $column => $source) {
      if ($target_definition->isUnique($column)) {
        $row['unique'][$column] = [
          '#title' => $this->t('Unique'),
          '#type' => 'checkbox',
          '#default_value' => !empty($mappings[$delta]['unique'][$column]),
          '#title_display' => 'invisible',
        ];
      }
      else {
        $row['unique']['#markup'] = '';
      }
    }

    if ($delta != $ajax_delta) {
      $row['remove'] = $default_button + [
        '#title' => $this->t('Remove'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title_display' => 'invisible',
        '#parents' => ['remove_mappings', $delta],
        '#remove' => TRUE,
      ];
    }
    else {
      $row['remove']['#markup'] = '';
    }

    return $row;
  }

  /**
   * Builds the form for entering a new custom source.
   *
   * @param array $element
   *   The element to which the subform is added.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $delta
   *   The row on the mapping form.
   * @param string $column
   *   The mapping source property.
   */
  protected function buildCustomSourceForms(array &$element, FormStateInterface $form_state, $delta, $column) {
    $supported_custom_source_plugins = $this->feedType->getParser()->getSupportedCustomSourcePlugins();
    $supported_custom_source_plugins[] = 'blank';
    foreach ($supported_custom_source_plugins as $custom_source_plugin_id) {
      $element['custom__' . $custom_source_plugin_id] = [
        '#type' => 'container',
        '#delta' => $delta,
        '#column' => $column,
        '#machine_name_source' => 'value',
      ];

      $plugin_state = $this->createSubFormState($custom_source_plugin_id . '_' . $delta . '_' . $column . '_configuration', $form_state);
      $plugin = $this->customSourcePluginManager->createInstance($custom_source_plugin_id, [
        'feed_type' => $this->feedType,
      ]);
      $element['custom__' . $custom_source_plugin_id] = $plugin->buildConfigurationForm($element['custom__' . $custom_source_plugin_id], $plugin_state);

      foreach (Element::children($element['custom__' . $custom_source_plugin_id]) as $field) {
        $element['custom__' . $custom_source_plugin_id][$field]['#states']['visible'][':input[name="mappings[' . $delta . '][map][' . $column . '][select]"]'] = ['value' => 'custom__' . $custom_source_plugin_id];
      }

      // Add machine name.
      $element['custom__' . $custom_source_plugin_id]['machine_name'] = [
        '#type' => 'machine_name',
        '#machine_name' => [
          'exists' => [$this, 'customSourceExists'],
          'source' => [
            'mappings',
            $delta,
            'map',
            $column,
            'custom__' . $custom_source_plugin_id,
            $element['custom__' . $custom_source_plugin_id]['#machine_name_source'],
          ],
          'standalone' => TRUE,
          'label' => '',
        ],
        '#default_value' => '',
        '#required' => FALSE,
        '#disabled' => '',
        '#weight' => -1,
      ];
    }

    // Add the appropriate new custom source options to the select source
    // dropdown.
    $options = $element['select']['#options'] ?? [];
    $new = (string) $this->t('New...');
    $element['select']['#options'] = [$new => $this->getCustomSourceOptions()] + $options;
  }

  /**
   * Checks if the given plugin has a settings form.
   *
   * @param \Drupal\feeds\Plugin\Type\Target\TargetInterface $plugin
   *   The target plugin.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return bool
   *   TRUE if it has a settings form. False otherwise.
   */
  protected function pluginHasSettingsForm(TargetInterface $plugin, FormStateInterface $form_state) {
    if (!$plugin instanceof ConfigurableTargetInterface) {
      // Target is not configurable.
      return FALSE;
    }

    if (!$plugin instanceof PluginFormInterface) {
      // Target plugin does not provide a settings form.
      return FALSE;
    }

    $settings_form = $plugin->buildConfigurationForm([], $form_state);
    return !empty($settings_form);
  }

  /**
   * Builds the summary for a configurable target.
   *
   * @param \Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface $plugin
   *   A configurable target plugin.
   *
   * @return array
   *   A renderable array.
   */
  protected function buildSummary(ConfigurableTargetInterface $plugin) {
    // Display a summary of the current plugin settings.
    $summary = $plugin->getSummary();
    if (!empty($summary)) {
      if (!is_array($summary)) {
        $summary = [$summary];
      }

      return [
        '#type' => 'inline_template',
        '#template' => '<div class="plugin-summary">{{ summary|safe_join("<br />") }}</div>',
        '#context' => ['summary' => $summary],
        '#cell_attributes' => ['class' => ['plugin-summary-cell']],
      ];
    }

    return [];
  }

  /**
   * Builds legend which explains source and target elements.
   *
   * @param array $form
   *   The complete mapping form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The legend form element.
   */
  protected function buildLegend(array $form, FormStateInterface $form_state) {
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Legend'),
      'sources' => [
        '#caption' => $this->t('Sources'),
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Machine name'),
          $this->t('Type'),
          $this->t('Description'),
        ],
        '#rows' => [],
      ],
      'targets' => [
        '#caption' => $this->t('Targets'),
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Machine name'),
          $this->t('Description'),
        ],
        '#rows' => [],
      ],
    ];

    foreach ($this->getMappingSourcesPerType() as $type => $sources) {
      foreach ($sources as $key => $source) {
        $element['sources']['#rows'][$key] = [
          'label' => $source['label'],
          'name' => $key,
          'type' => $source['type'],
          'description' => $source['description'] ?? NULL,
        ];
      }
    }

    /** @var \Drupal\feeds\TargetDefinitionInterface $definition */
    foreach ($this->targets as $key => $definition) {
      $element['targets']['#rows'][$key] = [
        'label' => $definition->getLabel(),
        'name' => $key,
        'description' => $definition->getDescription(),
      ];
    }

    return $element;
  }

  /**
   * Checks if a particular source already exists on the saved feed type.
   *
   * @param string $name
   *   The name to check.
   * @param array $element
   *   The form element using the machine name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return bool
   *   True if the source already exists, false otherwise.
   */
  public function customSourceExists($name, array $element, FormStateInterface $form_state) {
    // Get unchanged feed type.
    $unchanged_feed_type = $this->feedTypeStorage->loadUnchanged($this->feedType->getOriginalId());
    // Check if the custom source already exists on the last saved feed type.
    if ($unchanged_feed_type && $unchanged_feed_type->customSourceExists($name)) {
      return TRUE;
    }

    // Get the delta and the column of the passed form element. The delta is the
    // position of the mapping row on the form, the column refers to a property
    // of the target plugin.
    $element_delta = $element['#array_parents'][1];
    $element_column = $element['#array_parents'][3];

    // Check other mappings.
    foreach ($form_state->getValue('mappings') as $delta => $mapping) {
      foreach ($mapping['map'] as $column => $value) {
        // Check if this value belongs to our own element.
        if ($delta == $element_delta && $element_column == $column) {
          // Don't compare name to our own element.
          continue;
        }

        // Check if for this mapping row a new source is selected.
        $select = $value['select'];
        if (strpos($select, 'custom__') === 0) {
          // Compare the new source's name with the name to check.
          $map_name = $mappings[$delta]['map'][$column] = $value[$select]['machine_name'];
          if ($name == $map_name) {
            // Name is already used by an other mapper.
            return TRUE;
          }
        }
      }
    }

    // Name does not exist yet for custom source.
    return FALSE;
  }

  /**
   * Returns a list of custom source options, used by the mapping form.
   *
   * @return array
   *   A list of custom source options using id => label.
   */
  protected function getCustomSourceOptions(): array {
    $custom_sources = [];
    $supported_custom_source_plugins = $this->feedType->getParser()->getSupportedCustomSourcePlugins();
    // The blank source plugin is available for all parsers.
    $supported_custom_source_plugins[] = 'blank';

    foreach ($supported_custom_source_plugins as $custom_source_plugin_id) {
      $custom_source_plugin = $this->customSourcePluginManager->createInstance($custom_source_plugin_id, [
        'feed_type' => $this->feedType,
      ]);
      $custom_sources['custom__' . $custom_source_plugin_id] = $this->t('New @type source...', [
        '@type' => $custom_source_plugin->getLabel(),
      ]);
    }

    return $custom_sources;
  }

  /**
   * Processes the form state, populating the mappings on the feed type.
   *
   * @param array $form
   *   The complete mapping form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function processFormState(array $form, FormStateInterface $form_state) {
    $custom_source_prefix_length = strlen('custom__');

    // Process any plugin configuration.
    $triggering_element = $form_state->getTriggeringElement() + ['#op' => ''];
    if ($triggering_element['#op'] === 'update') {
      $this->feedType->getTargetPlugin($triggering_element['#delta'])->submitConfigurationForm($form, $form_state);
    }

    $mappings = $this->feedType->getMappings();
    foreach (array_filter((array) $form_state->getValue('mappings', [])) as $delta => $mapping) {
      foreach ($mapping['map'] as $column => $value) {
        $selected_source = $value['select'];
        if (strpos($selected_source, 'custom__') === 0) {
          // Add a new source.
          $source_name = $value[$selected_source]['machine_name'];
          $source_values = $value[$selected_source] + [
            'type' => substr($selected_source, $custom_source_prefix_length),
          ];
          if (empty($source_values['label'])) {
            $source_values['label'] = $value[$selected_source]['value'];
          }
          $this->feedType->addCustomSource($source_name, $source_values);
          $mappings[$delta]['map'][$column] = $source_name;
        }
        else {
          $mappings[$delta]['map'][$column] = $selected_source;
        }
      }
      if (isset($mapping['unique'])) {
        $mappings[$delta]['unique'] = array_filter($mapping['unique']);
      }
    }
    $this->feedType->setMappings($mappings);

    // Remove any mappings.
    foreach (array_keys(array_filter($form_state->getValue('remove_mappings', []))) as $delta) {
      $this->feedType->removeMapping($delta);
    }

    // Add any targets.
    if ($new_target = $form_state->getValue('add_target')) {
      $map = array_fill_keys($this->targets[$new_target]->getProperties(), '');
      $this->feedType->addMapping([
        'target' => $new_target,
        'map' => $map,
      ]);
    }

    // Allow the #default_value of 'add_target' to be reset.
    $input =& $form_state->getUserInput();
    unset($input['add_target']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form_state->getTriggeringElement()['#delta'])) {
      $delta = $form_state->getTriggeringElement()['#delta'];
      $this->feedType->getTargetPlugin($delta)->validateConfigurationForm($form, $form_state);
      $form_state->setRebuild();
    }
    else {
      // Allow plugins to validate the mapping form.
      foreach ($this->feedType->getPlugins() as $plugin) {
        if ($plugin instanceof MappingPluginFormInterface) {
          $plugin->mappingFormValidate($form, $form_state);
        }
      }
    }

    // Validate custom source forms.
    $mappings = $form_state->getValue('mappings');
    if (!empty($mappings)) {
      foreach ($mappings as $delta => $mapping) {
        foreach ($mapping['map'] as $column => $value) {
          // Check if for this mapping row a new source is selected.
          $select = $value['select'];
          if (strpos($select, 'custom__') === 0) {
            $custom_source_plugin_id = substr($select, strlen('custom__'));
            $form_state_key = [
              'mappings',
              $delta,
              'map',
              $column,
              $select,
            ];
            $plugin_state = $this->createSubFormState($form_state_key, $form_state);
            $plugin = $this->customSourcePluginManager->createInstance($custom_source_plugin_id, [
              'feed_type' => $this->feedType,
            ]);
            $element = $form['mappings'][$delta]['map'][$column][$select];
            $plugin->validateConfigurationForm($element, $plugin_state);

            // Move errors to form_state above.
            foreach ($plugin_state->getErrors() as $name => $error) {
              $form_state->setErrorByName($name, $error);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->processFormState($form, $form_state);

    // Allow plugins to hook into the mapping form.
    foreach ($this->feedType->getPlugins() as $plugin) {
      if ($plugin instanceof MappingPluginFormInterface) {
        $plugin->mappingFormSubmit($form, $form_state);
      }
    }

    $this->feedType->save();
  }

  /**
   * Builds an options list from mapping sources or targets.
   *
   * @param array $options
   *   The options to sort.
   *
   * @return array
   *   The sorted options.
   */
  protected function sortOptions(array $options) {
    $result = [];
    foreach ($options as $k => $v) {
      if (is_array($v) && !empty($v['label'])) {
        $result[$k] = $v['label'];
      }
      elseif (is_array($v)) {
        $result[$k] = $k;
      }
      else {
        $result[$k] = $v;
      }
    }
    asort($result);

    return $result;
  }

  /**
   * Callback for ajax requests.
   *
   * @return array
   *   The form element to return.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Page title callback.
   *
   * @return string
   *   The title of the mapping page.
   */
  public function mappingTitle(FeedTypeInterface $feeds_feed_type) {
    return $this->t('Mappings @label', ['@label' => $feeds_feed_type->label()]);
  }

  /**
   * Creates a FormStateInterface object for a plugin.
   *
   * @param string|array $key
   *   The form state key.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to copy values from.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   A new form state object.
   *
   * @see FormStateInterface::getValue()
   */
  protected function createSubFormState($key, FormStateInterface $form_state) {
    // There might turn out to be other things that need to be copied and passed
    // into plugins. This works for now.
    return (new FormState())->setValues($form_state->getValue($key, []));
  }

  /**
   * Returns available mapping sources, categorized per type.
   *
   * @return array
   *   An array of mapping sources, grouped by type.
   *   Each mapping source contains the following:
   *   - label (string): the source's label.
   *   - type (string): the source's type. This can refer to the custom source
   *     type, in case the source is a custom source.
   *   - description (string, optional): if available, the source's description.
   *   - machine_name (string, optional): for custom sources, a machine name is
   *     defined.
   *   Each source can have more properties, this can differ per type.
   */
  protected function getMappingSourcesPerType(): array {
    $sources = [];
    foreach ($this->feedType->getMappingSources() as $key => $source) {
      if (!strlen($key)) {
        continue;
      }

      // Determine the type of the source. This is used to group sources of the
      // same type.
      if (!isset($source['type'])) {
        $type = (string) $this->t('Predefined');
      }
      else {
        $type = $source['type'];

        // If a source is custom, get the label of the custom source type and
        // use that to group custom sources of the same type.
        $definition = $this->customSourcePluginManager->getDefinition($type, FALSE);
        if (isset($definition['title'])) {
          $type = (string) $definition['title'];
        }
      }

      $source['type'] = $type;
      $sources[$type][$key] = $source;
    }

    return $sources;
  }

  /**
   * Displays a warning when two duplicate configured mappings are found.
   *
   * Two mappings are considered a duplicate if they are configured the same. So
   * the same source, the same target and the same target configuration.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type.
   * @param array $target_options
   *   The mapping sources target list.
   */
  protected function checkDuplicateMappings(FeedTypeInterface $feed_type, array $target_options) {
    $output = [];
    $existing_mappings = $feed_type->getMappings();
    $existing_mappings_json_strings = array_map(
      static function ($item) {
        return json_encode($item, JSON_THROW_ON_ERROR);
      }, $existing_mappings
    );
    $count_existing_mappings = array_count_values($existing_mappings_json_strings);
    $duplicates = [];
    foreach ($count_existing_mappings as $key => $count) {
      if ($count > 1) {
        $duplicates[] = json_decode($key, TRUE, 512, JSON_THROW_ON_ERROR);
      }
    }

    $duplicates = array_map(
      function ($item) use ($target_options) {
        return $this->t('The target %target pairs more than once with the same source and the same settings.', [
          '%target' => $target_options[$item['target']],
        ]);
      }, $duplicates
    );

    $message = array_filter(array_merge($output, $duplicates), 'strlen');
    !empty($message) ? $this->messenger()->addWarning(Markup::create(implode('<br />', $message))) : TRUE;
  }

}
