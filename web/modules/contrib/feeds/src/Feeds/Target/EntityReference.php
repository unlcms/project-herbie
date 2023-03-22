<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\feeds\EntityFinderInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\feeds\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity reference mapper.
 *
 * @FeedsTarget(
 *   id = "entity_reference",
 *   field_types = {"entity_reference"}
 * )
 */
class EntityReference extends FieldTargetBase implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Feeds entity finder service.
   *
   * @var \Drupal\feeds\EntityFinderInterface
   */
  protected $entityFinder;

  /**
   * Constructs a new EntityReference object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\feeds\EntityFinderInterface $entity_finder
   *   The Feeds entity finder service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityFinderInterface $entity_finder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityFinder = $entity_finder;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('feeds.entity_finder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    // Only reference content entities. Configuration entities will need custom
    // targets.
    $type = $field_definition->getSetting('target_type');
    if (!\Drupal::entityTypeManager()->getDefinition($type)->entityClassImplements(ContentEntityInterface::class)) {
      return;
    }

    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $raw_values) {
    $values = [];
    foreach ($raw_values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $values[] = $columns;
      }
      catch (ReferenceNotFoundException $e) {
        // The referenced entity is not found. We need to enforce Feeds to try
        // to import the same item again on the next import.
        // Feeds stores a hash of every imported item in order to make the
        // import process more efficient by ignoring items it has already seen.
        // In this case we need to destroy the hash in order to be able to
        // import the reference on a next import.
        $entity->get('feeds_item')->getItemByFeed($feed)->hash = NULL;
        $feed->getState(StateInterface::PROCESS)->setMessage($e->getFormattedMessage(), 'warning', TRUE);
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        $this->addMessage($e->getFormattedMessage(), 'error');
      }
    }

    if (!empty($values)) {
      $entity_target = $this->getEntityTarget($feed, $entity);
      if ($entity_target) {
        $item_list = $entity_target->get($field_name);

        // Append these values to the existing values.
        $values = array_merge($item_list->getValue(), $values);

        $item_list->setValue($values);
      }
    }
  }

  /**
   * Returns a list of fields that may be used to reference by.
   *
   * @return array
   *   A list subfields of the entity reference field.
   */
  protected function getPotentialFields() {
    $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->getEntityType());
    $field_definitions = array_filter($field_definitions, [
      $this,
      'filterFieldTypes',
    ]);
    $options = [];
    foreach ($field_definitions as $id => $definition) {
      $options[$id] = Html::escape($definition->getLabel());
    }

    return $options;
  }

  /**
   * Callback for the potential field filter.
   *
   * Checks whether the provided field is available to be used as reference.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field
   *   The field to check.
   *
   * @return bool
   *   TRUE if the field can be used as reference otherwise FALSE.
   *
   * @see ::getPotentialFields()
   */
  protected function filterFieldTypes(FieldStorageDefinitionInterface $field) {
    if ($field instanceof DataDefinitionInterface && $field->isComputed()) {
      return FALSE;
    }

    switch ($field->getType()) {
      case 'integer':
      case 'string':
      case 'text_long':
      case 'path':
      case 'uuid':
      case 'feeds_item':
        return TRUE;

      default:
        return FALSE;
    }
  }

  /**
   * Returns the entity type to reference.
   *
   * @return string
   *   The entity type to reference.
   */
  protected function getEntityType() {
    return $this->settings['target_type'];
  }

  /**
   * Returns a list of bundles that may be referenced.
   *
   * If there are no target bundles configured on the entity reference field, an
   * empty array is returned.
   *
   * @return array
   *   Bundles that are allowed to be referenced.
   */
  protected function getBundles() {
    if (!empty($this->settings['handler_settings']['target_bundles'])) {
      return $this->settings['handler_settings']['target_bundles'];
    }
    return [];
  }

  /**
   * Returns the entity type's bundle key.
   *
   * @return string
   *   The bundle key of the entity type.
   */
  protected function getBundleKey() {
    return $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('bundle');
  }

  /**
   * Returns the entity type's label key.
   *
   * @return string
   *   The label key of the entity type.
   */
  protected function getLabelKey() {
    return $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('label');
  }

  /**
   * Returns the entity type's langcode key, if it has one.
   *
   * @return string|null
   *   The langcode key of the entity type.
   */
  protected function getLangcodeKey() {
    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    if ($entity_type->hasKey('langcode')) {
      return $entity_type->getKey('langcode');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // Check if there is a value for target ID.
    if (!isset($values['target_id']) || strlen(trim($values['target_id'])) === 0) {
      // No value.
      throw new EmptyFeedException();
    }

    $target_ids = $this->findEntities($this->configuration['reference_by'], $values['target_id']);
    if (empty($target_ids)) {
      throw new ReferenceNotFoundException($this->t('Referenced entity not found for field %field with value %target_id.', [
        '%target_id' => $values['target_id'],
        '%field' => $this->configuration['reference_by'],
      ]));
    }

    $values['target_id'] = reset($target_ids);
  }

  /**
   * Searches for an entity by entity key.
   *
   * @param string $field
   *   The subfield to search in.
   * @param string $search
   *   The value to search for.
   *
   * @return int|bool
   *   The entity id, or false, if not found.
   */
  protected function findEntity(string $field, $search) {
    $entities = $this->findEntities($field, $search);
    if (!empty($entities)) {
      return reset($entities);
    }
    return FALSE;
  }

  /**
   * Tries to lookup an existing entity.
   *
   * @param string $field
   *   The subfield to search in.
   * @param string|int $search
   *   The value to lookup.
   *
   * @return int[]
   *   A list of entity ID's.
   */
  protected function findEntities(string $field, $search) {
    if ($field == 'feeds_item') {
      $field = 'feeds_item.' . $this->configuration['feeds_item'];
    }

    $target_ids = $this->entityFinder->findEntities($this->getEntityType(), $field, $search, $this->getBundles());
    if (!empty($target_ids)) {
      return $target_ids;
    }

    if ($this->configuration['autocreate'] && $field === $this->getLabelKey()) {
      return [$this->createEntity($search)];
    }

    return [];
  }

  /**
   * Creates a new entity with the given label and saves it.
   *
   * @param string $label
   *   The label the new entity should get.
   *
   * @return int|string|false
   *   The ID of the new entity or false if the given label is empty.
   */
  protected function createEntity($label) {
    if (!is_string($label) || !strlen(trim($label))) {
      return FALSE;
    }

    $bundles = $this->getBundles();

    // Create values for the new entity.
    $values = [
      $this->getLabelKey() => $label,
      $this->getBundleKey() => reset($bundles),
    ];
    // Set language if the entity type supports it.
    if ($langcode = $this->getLangcodeKey()) {
      $values[$langcode] = $this->getLangcode();
    }

    $entity = $this->entityTypeManager->getStorage($this->getEntityType())->create($values);

    $entity->save();

    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration() + [
      'reference_by' => $this->getLabelKey(),
      'autocreate' => FALSE,
    ];
    if (array_key_exists('feeds_item', $this->getPotentialFields())) {
      $config['feeds_item'] = FALSE;
    }
    return $config;
  }

  /**
   * Returns options for feeds_item configuration.
   */
  public function getFeedsItemOptions() {
    return [
      'guid' => $this->t('Item GUID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = $this->getPotentialFields();

    // Hack to find out the target delta.
    $delta = 0;
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'target-settings-') === 0) {
        [, , $delta] = explode('-', $key);
        break;
      }
    }

    $form['reference_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference by'),
      '#options' => $options,
      '#default_value' => $this->configuration['reference_by'],
    ];

    $feed_item_options = $this->getFeedsItemOptions();

    $form['feeds_item'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed item'),
      '#options' => $feed_item_options,
      '#default_value' => $this->getConfiguration('feeds_item'),
      '#states' => [
        'visible' => [
          ':input[name="mappings[' . $delta . '][settings][reference_by]"]' => [
            'value' => 'feeds_item',
          ],
        ],
      ],
    ];

    $form['autocreate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autocreate entity'),
      '#default_value' => $this->configuration['autocreate'],
      '#states' => [
        'visible' => [
          ':input[name="mappings[' . $delta . '][settings][reference_by]"]' => [
            'value' => $this->getLabelKey(),
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getPotentialFields();

    $summary = parent::getSummary();

    if ($this->configuration['reference_by'] && isset($options[$this->configuration['reference_by']])) {
      $summary[] = $this->t('Reference by: %message', ['%message' => $options[$this->configuration['reference_by']]]);
      if ($this->configuration['reference_by'] == 'feeds_item') {
        $feed_item_options = $this->getFeedsItemOptions();
        $summary[] = $this->t('Feed item: %feed_item', ['%feed_item' => $feed_item_options[$this->configuration['feeds_item']]]);
      }
    }
    else {
      $summary[] = [
        '#prefix' => '<div class="messages messages--warning">',
        '#markup' => $this->t('Please select a field to reference by.'),
        '#suffix' => '</div>',
      ];
    }

    if ($this->configuration['reference_by'] === $this->getLabelKey()) {
      $create = $this->configuration['autocreate'] ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Autocreate terms: %create', ['%create' => $create]);
    }

    return $summary;
  }

}
