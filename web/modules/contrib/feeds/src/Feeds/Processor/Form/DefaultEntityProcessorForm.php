<?php

namespace Drupal\feeds\Feeds\Processor\Form;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The configuration form for the entity processor.
 */
class DefaultEntityProcessorForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $actionManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $userSettings;

  /**
   * Constructs a DefaultEntityProcessorForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Config\Config $user_settings
   *   The user settings.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PluginManagerInterface $action_manager, DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, Config $user_settings) {
    $this->entityTypeManager = $entity_type_manager;
    $this->actionManager = $action_manager;
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userSettings = $user_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.action'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('config.factory')->get('user.settings'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $tokens = [
      '@entity' => mb_strtolower($this->plugin->entityTypeLabel()),
      '@entities' => mb_strtolower($this->plugin->entityTypeLabelPlural()),
    ];
    $entity_type = $this->entityTypeManager->getDefinition($this->plugin->entityType());

    if ($entity_type->getKey('langcode')) {
      $langcode = $this->plugin->getConfiguration('langcode');

      $form['langcode'] = [
        '#type' => 'select',
        '#options' => $this->plugin->languageOptions(),
        '#title' => $this->t('Language'),
        '#required' => TRUE,
        '#default_value' => $langcode,
      ];

      // Add default value as one of the options if not yet available.
      if ($langcode && !isset($form['langcode']['#options'][$langcode])) {
        $form['langcode']['#options'][$langcode] = $this->t('Unknown language: @language', [
          '@language' => $langcode,
        ]);
      }
    }
    $form['insert_new'] = [
      '#type' => 'radios',
      '#title' => $this->t('Insert new @entities', $tokens),
      '#description' => $this->t('New @entities will be determined using mappings that are a "unique target".', $tokens),
      '#options' => [
        ProcessorInterface::INSERT_NEW => $this->t('Insert new @entities', $tokens),
        ProcessorInterface::SKIP_NEW => $this->t('Do not insert new @entities', $tokens),
      ],
      '#default_value' => $this->plugin->getConfiguration('insert_new'),
    ];

    $form['update_existing'] = [
      '#type' => 'radios',
      '#title' => $this->t('Update existing @entities', $tokens),
      '#description' => $this->t('Existing @entities will be determined using mappings that are <strong>unique</strong>.', $tokens),
      '#options' => [
        ProcessorInterface::SKIP_EXISTING => $this->t('Do not update existing @entities', $tokens),
        ProcessorInterface::REPLACE_EXISTING => $this->t('Replace existing @entities', $tokens),
        ProcessorInterface::UPDATE_EXISTING => $this->t('Update existing @entities', $tokens),
      ],
      '#default_value' => $this->plugin->getConfiguration('update_existing'),
    ];

    $times = [
      ProcessorInterface::EXPIRE_NEVER,
      3600,
      10800,
      21600,
      43200,
      86400,
      259200,
      604800,
      2592000,
      2592000 * 3,
      2592000 * 6,
      31536000,
    ];
    $period = array_map([$this, 'formatExpire'], array_combine($times, $times));

    $options = $this->getUpdateNonExistentActions();
    $selected = $this->plugin->getConfiguration('update_non_existent');
    if (!isset($options[$selected])) {
      $options[$selected] = $this->t('@label (action no longer available)', [
        '@label' => $selected,
      ]);
    }
    if (!empty($options)) {
      $form['update_non_existent'] = [
        '#type' => 'select',
        '#title' => $this->t('Previously imported items'),
        '#description' => $this->t('Select what to do with items that were previously imported, but are now no longer in the feed.'),
        '#options' => $options,
        '#default_value' => $this->plugin->getConfiguration('update_non_existent'),
      ];
    }

    $form['expire'] = [
      '#type' => 'select',
      '#title' => $this->t('Expire @entities', $tokens),
      '#options' => $period,
      '#description' => $this->t('Select after how much time @entities should be deleted.', $tokens),
      '#default_value' => $this->plugin->getConfiguration('expire'),
    ];

    if ($entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      $form['owner_feed_author'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Owner: Feed author'),
        '#description' => $this->t('Use the feed author as the owner of the entities to be created.'),
        '#default_value' => $this->plugin->getConfiguration('owner_feed_author'),
      ];

      $form['owner_id'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Owner'),
        '#description' => $this->t('Select the owner of the entities to be created. Leave blank for %anonymous.', ['%anonymous' => $this->userSettings->get('anonymous')]),
        '#target_type' => 'user',
        '#default_value' => $this->userStorage->load($this->plugin->getConfiguration('owner_id')),
        '#states' => [
          'invisible' => [
            'input[name="processor_configuration[owner_feed_author]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['advanced'] = [
      '#title' => $this->t('Advanced settings'),
      '#type' => 'details',
      '#collapsed' => TRUE,
      '#collapsible' => TRUE,
      '#weight' => 10,
    ];

    if ($entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      $form['advanced']['authorize'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Authorize'),
        '#description' => $this->t('Check that the author has permission to create the @entity.', $tokens),
        '#default_value' => $this->plugin->getConfiguration('authorize'),
        '#parents' => ['processor_configuration', 'authorize'],
      ];
    }

    if ($entity_type->entityClassImplements('\Drupal\Core\Entity\RevisionableInterface')) {
      $form['advanced']['revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('New revision'),
        '#description' => $this->t('Save as new revision.'),
        '#default_value' => $this->plugin->getConfiguration('revision'),
        '#parents' => ['processor_configuration', 'revision'],
      ];
    }

    $form['advanced']['skip_hash_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force update'),
      '#description' => $this->t('Forces the update of items even if the feed did not change.'),
      '#default_value' => $this->plugin->getConfiguration('skip_hash_check'),
      '#parents' => ['processor_configuration', 'skip_hash_check'],
      '#states' => [
        'visible' => [
          'input[name="processor_configuration[update_existing]"]' => [
            'value' => ProcessorInterface::UPDATE_EXISTING,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('owner_id', (int) $form_state->getValue('owner_id', 0));

    // Check if the selected option for 'update_non_existent' is still
    // available.
    $options = $this->getUpdateNonExistentActions();
    $selected = $form_state->getValue('update_non_existent');
    if (!isset($options[$selected])) {
      $form_state->setError($form['update_non_existent'], $this->t('The option %label is no longer available. Please select a different option.', [
        '%label' => $selected,
      ]));
    }
  }

  /**
   * Formats UNIX timestamps to readable strings.
   *
   * @param int $timestamp
   *   A UNIX timestamp.
   *
   * @return string
   *   A string in the format, "After (time)" or "Never."
   */
  public function formatExpire($timestamp) {
    if ($timestamp == ProcessorInterface::EXPIRE_NEVER) {
      return $this->t('Never');
    }

    return $this->t('after @time', ['@time' => $this->dateFormatter->formatInterval($timestamp)]);
  }

  /**
   * Get available actions to apply on the entity.
   *
   * @return array
   *   A list of applicable actions.
   */
  protected function getUpdateNonExistentActions() {
    $options = [];

    $action_definitions = $this->actionManager->getDefinitionsByType($this->plugin->entityType());
    foreach ($action_definitions as $id => $definition) {
      // Filter out configurable actions.
      $interfaces = class_implements($definition['class']);
      if (isset($interfaces[ConfigurableInterface::class])) {
        continue;
      }
      // @todo remove when Drupal 8 support has ended.
      if (isset($interfaces['Drupal\Component\Plugin\ConfigurablePluginInterface'])) {
        continue;
      }

      // Filter out actions that need confirmation.
      if (!empty($definition['confirm_form_route_name'])) {
        continue;
      }

      // Check for deprecated action plugins.
      foreach ($this->getDeprecatedActionClasses() as $deprecated_class_name) {
        if ($definition['class'] === $deprecated_class_name || is_subclass_of($definition['class'], $deprecated_class_name)) {
          continue 2;
        }
      }

      $options[$id] = $definition['label'];
    }

    return [
      '_keep' => $this->t('Keep'),
      '_delete' => $this->t('Delete'),
    ] + $options;
  }

  /**
   * Returns a list of classes from deprecated action plugins.
   *
   * @return string[]
   *   An array of class names.
   */
  protected function getDeprecatedActionClasses() {
    // @todo remove when Drupal 8 support has ended.
    return [
      'Drupal\comment\Plugin\Action\PublishComment',
      'Drupal\comment\Plugin\Action\UnpublishComment',
      'Drupal\comment\Plugin\Action\SaveComment',
      'Drupal\node\Plugin\Action\PublishNode',
      'Drupal\node\Plugin\Action\UnpublishNode',
      'Drupal\node\Plugin\Action\SaveNode',
    ];
  }

}
