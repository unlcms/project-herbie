<?php

namespace Drupal\feeds;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\feeds\Ajax\SetHashCommand;
use Drupal\feeds\Plugin\PluginFormFactory;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the feed type edit forms.
 */
class FeedTypeForm extends EntityForm {

  /**
   * The feed type storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $feedTypeStorage;

  /**
   * The form factory.
   *
   * @var \Drupal\feeds\Plugin\PluginFormFactory
   */
  protected $formFactory;

  /**
   * Provides a service to handle various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Turns a render array into a HTML string.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new FeedTypeForm object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $feed_type_storage
   *   The feed type storage controller.
   * @param \Drupal\feeds\Plugin\PluginFormFactory $factory
   *   The form factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The services of date.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render object.
   */
  public function __construct(ConfigEntityStorageInterface $feed_type_storage, PluginFormFactory $factory, DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->feedTypeStorage = $feed_type_storage;
    $this->formFactory = $factory;
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('feeds_feed_type'),
      $container->get('feeds_plugin_form_factory'),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $values = $form_state->getValues();

    $form['#attached']['library'][] = 'feeds/feeds';

    $form['basics'] = [
      '#title' => $this->t('Basic settings'),
      '#type' => 'details',
      '#open' => $this->entity->isNew(),
      '#tree' => FALSE,
    ];

    $form['basics']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
      '#description' => $this->t('A unique label for this feed type. This label will be displayed in the interface.'),
      '#required' => TRUE,
    ];

    $form['basics']['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#description' => $this->t('A unique name for this feed type. It must only contain lowercase letters, numbers and underscores.'),
      '#machine_name' => [
        'exists' => 'Drupal\feeds\Entity\FeedType::load',
        'source' => ['basics', 'label'],
      ],
      '#required' => TRUE,
    ];
    $form['basics']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A description of this feed type.'),
      '#default_value' => $this->entity->getDescription(),
    ];
    $form['basics']['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $this->entity->getHelp(),
      '#description' => $this->t('This text will be displayed at the top of the feed when creating or editing a feed of this type.'),
    ];

    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    $form['plugin_settings']['#prefix'] = '<div id="feeds-ajax-form-wrapper" class="feeds-feed-type-secondary-settings">';
    $form['plugin_settings']['#suffix'] = '</div>';

    $form['feed_type_settings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Settings'),
      '#tree' => FALSE,
    ];

    $times = [
      900,
      1800,
      3600,
      10800,
      21600,
      43200,
      86400,
      259200,
      604800,
      2419200,
    ];

    $period = array_map(function ($time) {
      return $this->dateFormatter->formatInterval($time);
    }, array_combine($times, $times));

    foreach ($period as &$p) {
      $p = $this->t('Every @p', ['@p' => $p]);
    }

    $period = [
      FeedTypeInterface::SCHEDULE_NEVER => $this->t('Off'),
      FeedTypeInterface::SCHEDULE_CONTINUOUSLY => $this->t('As often as possible'),
    ] + $period;

    $cron_required = [
      '#type' => 'link',
      '#url' => Url::fromUri('https://www.drupal.org/docs/user_guide/en/security-cron.html'),
      '#title' => $this->t('Requires cron to be configured.'),
      '#attributes' => [
        'target' => '_new',
      ],
    ];
    $form['feed_type_settings']['import_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Import period'),
      '#options' => $period,
      '#description' => $this->t('Choose how often a feed should be imported.') . ' ' . $this->renderer->renderRoot($cron_required),
      '#default_value' => $this->entity->getImportPeriod(),
    ];

    foreach ($this->entity->getPlugins() as $type => $plugin) {
      $options = $this->entity->getPluginOptionsList($type);
      natcasesort($options);

      $form[$type . '_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['feeds-plugin-inline']],
      ];

      if (count($options) === 1) {
        $form[$type . '_wrapper']['id'] = [
          '#type' => 'value',
          '#value' => $plugin->getPluginId(),
          '#plugin_type' => $type,
          '#parents' => [$type],
        ];
      }
      else {
        $form[$type . '_wrapper']['id'] = [
          '#type' => 'select',
          '#title' => $this->t('@type', ['@type' => ucfirst($type)]),
          '#options' => $options,
          '#default_value' => $plugin->getPluginId(),
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'feeds-ajax-form-wrapper',
            'progress' => 'none',
          ],
          '#plugin_type' => $type,
          '#parents' => [$type],
        ];
      }

      // Give lockable plugins a chance to lock themselves.
      // @see \Drupal\feeds\Feeds\Processor\EntityProcessor::isLocked()
      if ($plugin instanceof LockableInterface) {
        $form[$type . '_wrapper']['id']['#disabled'] = $plugin->isLocked();
      }

      $plugin_state = $this->createSubFormState($type . '_configuration', $form_state);

      // This is the small form that appears under the select box.
      if ($this->pluginHasForm($plugin, 'option')) {
        $option_form = $this->formFactory->createInstance($plugin, 'option');
        $form[$type . '_wrapper']['advanced'] = $option_form->buildConfigurationForm([], $plugin_state);
      }

      $form[$type . '_wrapper']['advanced']['#prefix'] = '<div id="feeds-plugin-' . $type . '-advanced">';
      $form[$type . '_wrapper']['advanced']['#suffix'] = '</div>';

      if ($this->pluginHasForm($plugin, 'configuration')) {
        $form_builder = $this->formFactory->createInstance($plugin, 'configuration');

        $plugin_form = $form_builder->buildConfigurationForm([], $plugin_state);
        $form[$type . '_configuration'] = [
          '#type' => 'details',
          '#group' => 'plugin_settings',
          '#title' => $this->t('@type settings', ['@type' => ucfirst($type)]),
        ];
        $form[$type . '_configuration'] += $plugin_form;
      }
    }

    $form_state->setValue($type . '_configuration', $plugin_state->getValues());

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if ($this->entity->isNew()) {
      $actions['submit']['#value'] = $this->t('Save and add mappings');
      $actions['submit']['#submit'][] = '::toMapping';
    }
    else {
      $actions['submit']['#value'] = $this->t('Save feed type');
    }

    return $actions;
  }

  /**
   * Returns the plugin forms for this feed type.
   *
   * @return \Drupal\feeds\Plugin\Type\ExternalPluginFormInterface[]
   *   A list of form objects, keyed by plugin id.
   */
  protected function getPluginForms() {
    $plugins = [];
    foreach ($this->entity->getPlugins() as $type => $plugin) {
      if ($this->pluginHasForm($plugin, 'configuration')) {
        $plugins[$type] = $this->formFactory->createInstance($plugin, 'configuration');
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return;
    }
    $values =& $form_state->getValues();

    // Moved advanced settings to regular settings.
    foreach (array_keys($this->entity->getPlugins()) as $type) {
      if (isset($values[$type . '_wrapper']['advanced'])) {
        if (!isset($values[$type . '_configuration'])) {
          $values[$type . '_configuration'] = [];
        }
        $values[$type . '_configuration'] += $values[$type . '_wrapper']['advanced'];
      }
      unset($values[$type . '_wrapper']);
    }

    foreach ($this->getPluginForms() as $type => $plugin) {
      if (!isset($form[$type . '_configuration'])) {
        // When switching from a non-configurable plugin to a configurable
        // plugin, no form is yet available. So skip validating it to avoid
        // fatal errors.
        continue;
      }

      $plugin_state = $this->createSubFormState($type . '_configuration', $form_state);
      $plugin->validateConfigurationForm($form[$type . '_configuration'], $plugin_state);
      $form_state->setValue($type . '_configuration', $plugin_state->getValues());

      $this->moveFormStateErrors($plugin_state, $form_state);
    }

    // Build the feed type object from the submitted values.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getPluginForms() as $type => $plugin) {
      $plugin_state = $this->createSubFormState($type . '_configuration', $form_state);
      $plugin->submitConfigurationForm($form[$type . '_configuration'], $plugin_state);
      $form_state->setValue($type . '_configuration', $plugin_state->getValues());
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $form_state->setRedirect('entity.feeds_feed_type.edit_form', ['feeds_feed_type' => $this->entity->id()]);
    $this->messenger()->addStatus($this->t('Your changes have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function toMapping(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('mapping'));
  }

  /**
   * Sends an ajax response.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()['#plugin_type'];

    $response = new AjaxResponse();

    // Set URL hash so that the correct settings tab is open.
    if (isset($form[$type . '_configuration']['#id'])) {
      $hash = ltrim($form[$type . '_configuration']['#id'], '#');
      $response->addCommand(new SetHashCommand($hash));
    }

    // Update the forms.
    $plugin_settings = $this->renderer->renderRoot($form['plugin_settings']);
    $advanced_settings = $this->renderer->renderRoot($form[$type . '_wrapper']['advanced']);
    $response->addCommand(new ReplaceCommand('#feeds-ajax-form-wrapper', $plugin_settings));
    $response->addCommand(new ReplaceCommand('#feeds-plugin-' . $type . '-advanced', $advanced_settings));

    // Add attachments.
    $attachments = NestedArray::mergeDeep($form['plugin_settings']['#attached'], $form[$type . '_wrapper']['advanced']['#attached']);
    $response->setAttachments($attachments);

    // Display status messages.
    $status_messages = ['#type' => 'status_messages'];
    $output = $this->renderer->renderRoot($status_messages);
    if (!empty($output)) {
      $response->addCommand(new HtmlCommand('.region-messages', $output));
    }

    return $response;
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
   * Moves form state errors from one form state to another.
   */
  protected function moveFormStateErrors(FormStateInterface $from, FormStateInterface $to) {
    foreach ($from->getErrors() as $name => $error) {
      $to->setErrorByName($name, $error);
    }
  }

  /**
   * Returns whether or not the plugin implements a form for the given type.
   *
   * @param \Drupal\feeds\Plugin\Type\FeedsPluginInterface $plugin
   *   The Feeds plugin.
   * @param string $operation
   *   The type of form to check for. See
   *   \Drupal\feeds\Plugin\PluginFormFactory::hasForm() for more information.
   *
   * @return bool
   *   True if the plugin implements a form of the given type. False otherwise.
   *
   * @see \Drupal\feeds\Plugin\PluginFormFactory::hasForm()
   */
  protected function pluginHasForm(FeedsPluginInterface $plugin, $operation) {
    return $this->formFactory->hasForm($plugin, $operation);
  }

}
