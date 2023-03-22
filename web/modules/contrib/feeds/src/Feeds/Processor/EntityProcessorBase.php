<?php

namespace Drupal\feeds\Feeds\Processor;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\feeds\Entity\FeedType;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\EntityAccessException;
use Drupal\feeds\Exception\MissingTargetException;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\State\CleanStateInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;
use Drupal\feeds\Plugin\Type\Target\TranslatableTargetInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\StateType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\EntityOwnerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base entity processor.
 *
 * Creates entities from feed items.
 */
abstract class EntityProcessorBase extends ProcessorBase implements EntityProcessorInterface, ContainerFactoryPluginInterface, MappingPluginFormInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity storage controller for the entity type being processed.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageController;

  /**
   * The entity info for the selected entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Flag indicating that this processor is locked.
   *
   * @var bool
   */
  protected $isLocked;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The datetime interface for getting the system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $actionManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger for feeds channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an EntityProcessorBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime service for getting the system time.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for the feeds channel.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, LanguageManagerInterface $language_manager, TimeInterface $date_time, PluginManagerInterface $action_manager, RendererInterface $renderer, LoggerInterface $logger, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityType = $entity_type_manager->getDefinition($plugin_definition['entity_type']);
    $this->storageController = $entity_type_manager->getStorage($plugin_definition['entity_type']);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->languageManager = $language_manager;
    $this->dateTime = $date_time;
    $this->actionManager = $action_manager;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->database = $database;

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
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.action'),
      $container->get('renderer'),
      $container->get('logger.factory')->get('feeds'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed, ItemInterface $item, StateInterface $state) {
    // Initialize clean list if needed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    if (!$clean_state->initiated()) {
      $this->initCleanList($feed, $clean_state);
    }

    $skip_new = $this->configuration['insert_new'] == static::SKIP_NEW;
    $existing_entity_id = $this->existingEntityId($feed, $item);
    $skip_existing = $this->configuration['update_existing'] == static::SKIP_EXISTING;

    // If the entity is an existing entity it must be removed from the clean
    // list.
    if ($existing_entity_id) {
      $clean_state->removeItem($existing_entity_id);
    }

    // Bulk load existing entities to save on db queries.
    if (($skip_existing && $existing_entity_id) || (!$existing_entity_id && $skip_new)) {
      $state->report(StateType::SKIP, 'Skipped because the entity already exists.', [
        'feed' => $feed,
        'item' => $item,
        'entity_label' => [$existing_entity_id, 'id'],
      ]);
      return;
    }

    // Delay building a new entity until necessary.
    if ($existing_entity_id) {
      $entity = $this->storageController->load($existing_entity_id);
    }

    $hash = $this->hash($item);
    $changed = $existing_entity_id && ($hash !== $entity->get('feeds_item')->getItemHashByFeed($feed));

    // Do not proceed if the item exists, has not changed, and we're not
    // forcing the update.
    if ($existing_entity_id && !$changed && !$this->configuration['skip_hash_check']) {
      $state->report(StateType::SKIP, 'Skipped because the source data has not changed.', [
        'feed' => $feed,
        'item' => $item,
        'entity' => $entity,
        'entity_label' => $this->identifyEntity($entity, $feed),
      ]);
      return;
    }

    // Build a new entity.
    if (!$existing_entity_id && !$skip_new) {
      $entity = $this->newEntity($feed);
    }

    try {
      // Set feeds_item values.
      $feeds_item = $entity->get('feeds_item')->getItemByFeed($feed, TRUE);
      $feeds_item->hash = $hash;

      // Set new revision if needed.
      if ($this->configuration['revision']) {
        $entity->setNewRevision(TRUE);
        $entity->setRevisionCreationTime($this->dateTime->getRequestTime());
      }

      // Set field values.
      $this->map($feed, $entity, $item);

      // Validate the entity.
      $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_PREVALIDATE, $entity, $item);
      $this->entityValidate($entity, $feed);

      // Dispatch presave event.
      $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_PRESAVE, $entity, $item);

      // This will throw an exception on failure.
      $this->entitySaveAccess($entity);
      // Set imported time.
      $feeds_item->imported = $this->dateTime->getRequestTime();

      // And... Save! We made it.
      $this->storageController->save($entity);

      // Dispatch postsave event.
      $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_POSTSAVE, $entity, $item);

      // Track progress.
      $operation = $existing_entity_id ? StateType::UPDATE : StateType::CREATE;
      $state->report($operation, '', [
        'feed' => $feed,
        'item' => $item,
        'entity' => $entity,
        'entity_label' => $this->identifyEntity($entity, $feed),
      ]);
    }
    catch (EmptyFeedException $e) {
      // Not an error.
      $state->report(StateType::SKIP, 'Skipped because a value appeared to be empty.', [
        'feed' => $feed,
        'item' => $item,
        'entity' => $entity,
        'entity_label' => $this->identifyEntity($entity, $feed),
      ]);
    }
    // Something bad happened, log it.
    catch (ValidationException $e) {
      $state->report(StateType::FAIL, $e->getFormattedMessage(), [
        'feed' => $feed,
        'item' => $item,
        'entity' => $entity,
        'entity_label' => $this->identifyEntity($entity, $feed),
      ]);
      $state->setMessage($e->getFormattedMessage(), 'warning');
    }
    catch (\Exception $e) {
      $state->report(StateType::FAIL, $e->getMessage(), [
        'feed' => $feed,
        'item' => $item,
        'entity' => $entity,
        'entity_label' => $this->identifyEntity($entity, $feed),
      ]);
      $state->setMessage($e->getMessage(), 'warning');
    }
  }

  /**
   * Initializes the list of entities to clean.
   *
   * This populates $state->cleanList with all existing entities previously
   * imported from the source.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import.
   * @param \Drupal\feeds\Feeds\State\CleanStateInterface $state
   *   The state of the clean stage.
   */
  protected function initCleanList(FeedInterface $feed, CleanStateInterface $state) {
    $state->setEntityTypeId($this->entityType());

    // Fill the list only if needed.
    if ($this->getConfiguration('update_non_existent') === static::KEEP_NON_EXISTENT) {
      return;
    }

    // Set list of entities to clean.
    $ids = $this->entityTypeManager
      ->getStorage($this->entityType())
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('feeds_item.target_id', $feed->id())
      ->condition('feeds_item.hash', $this->getConfiguration('update_non_existent'), '<>')
      ->execute();
    $state->setList($ids);

    // And set progress.
    $state->total = $state->count();
    $state->progress($state->total, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function clean(FeedInterface $feed, EntityInterface $entity, CleanStateInterface $state) {
    $update_non_existent = $this->getConfiguration('update_non_existent');
    if ($update_non_existent === static::KEEP_NON_EXISTENT) {
      // No action to take on this entity.
      return;
    }

    $message = '';
    $variables = [];

    switch ($update_non_existent) {
      case static::KEEP_NON_EXISTENT:
        // No action to take on this entity.
        return;

      case static::DELETE_NON_EXISTENT:
        $entity->delete();
        $message = 'Deleted because the item was no longer in the source.';
        break;

      default:
        try {
          // Apply action on entity.
          $action = $this->actionManager->createInstance($update_non_existent);
          $action->execute($entity);
          $message = 'Applied action @action because the item was no longer in the source.';
          $variables['@action'] = $action->getPluginDefinition()['label'];
        }
        catch (PluginNotFoundException $e) {
          $state->setMessage($this->t('Cleaning %entity failed because of non-existing action plugin %name.', [
            '%entity' => $entity->label(),
            '%name' => $update_non_existent,
          ]), 'error');

          throw $e;
        }
        break;
    }

    // Check if the entity was deleted.
    $entity_reloaded = $this->storageController->load($entity->id());

    // If the entity was not deleted, update hash.
    if (isset($entity_reloaded->feeds_item)) {
      $entity_reloaded->get('feeds_item')->getItemByFeed($feed)->hash = $update_non_existent;
      $this->storageController->save($entity_reloaded);
    }

    // State progress.
    $state->report(StateType::CLEAN, $message, [
      'feed' => $feed,
      'entity' => $entity,
      'entity_label' => $this->identifyEntity($entity, $feed),
    ] + $variables);
    $state->progress($state->total, $state->cleaned);
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {
    // Build base select statement.
    $query = $this->entityTypeManager
      ->getStorage($this->entityType())
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('feeds_item.target_id', $feed->id());

    // If there is no total, query it.
    if (!$state->total) {
      $count_query = clone $query;
      $state->total = (int) $count_query->count()->execute();
    }

    // Delete a batch of entities.
    $entity_ids = $query->range(0, 10)->execute();

    if ($entity_ids) {
      $this->entityDeleteMultiple($entity_ids);
      $state->deleted += count($entity_ids);
      $state->progress($state->total, $state->deleted);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityType() {
    return $this->pluginDefinition['entity_type'];
  }

  /**
   * The entity's bundle key.
   *
   * @return string|null
   *   The bundle type this processor operates on, or null if it is undefined.
   */
  public function bundleKey() {
    return $this->entityType->getKey('bundle');
  }

  /**
   * Bundle type this processor operates on.
   *
   * Defaults to the entity type for entities that do not define bundles.
   *
   * @return string|null
   *   The bundle type this processor operates on, or null if it is undefined.
   *
   * @todo We should be more careful about missing bundles.
   */
  public function bundle() {
    if (!$bundle_key = $this->entityType->getKey('bundle')) {
      return $this->entityType();
    }
    if (isset($this->configuration['values'][$bundle_key])) {
      return $this->configuration['values'][$bundle_key];
    }
  }

  /**
   * Returns the bundle label for the entity being processed.
   *
   * @return string
   *   The bundle label.
   */
  public function bundleLabel() {
    if ($label = $this->entityType->getBundleLabel()) {
      return $label;
    }
    return $this->t('Bundle');
  }

  /**
   * Provides a list of bundle options for use in select lists.
   *
   * @return array
   *   A keyed array of bundle => label.
   */
  public function bundleOptions() {
    $options = [];
    foreach ($this->entityTypeBundleInfo->getBundleInfo($this->entityType()) as $bundle => $info) {
      if (!empty($info['label'])) {
        $options[$bundle] = $info['label'];
      }
      else {
        $options[$bundle] = $bundle;
      }
    }

    return $options;
  }

  /**
   * Provides a list of languages available on the site.
   *
   * @return array
   *   A keyed array of language_key => language_name.
   *   For example: 'en' => 'English').
   */
  public function languageOptions() {
    foreach ($this->languageManager->getLanguages(LanguageInterface::STATE_ALL) as $language) {
      $langcodes[$language->getId()] = $language->getName();
    }

    return $langcodes;
  }

  /**
   * Returns the label of the entity type being processed.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label of the entity type.
   */
  public function entityTypeLabel() {
    return $this->entityType->getLabel();
  }

  /**
   * Returns the plural label of the entity type being processed.
   *
   * @return string
   *   The plural label of the entity type.
   */
  public function entityTypeLabelPlural() {
    return $this->entityType->getPluralLabel();
  }

  /**
   * Returns the label for items being created, updated, or deleted.
   *
   * @return string
   *   The item label.
   */
  public function getItemLabel() {
    if (!$this->entityType->getKey('bundle') || !$this->entityType->getBundleEntityType()) {
      return $this->entityTypeLabel();
    }
    $storage = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType());
    return $storage->load($this->configuration['values'][$this->entityType->getKey('bundle')])->label();
  }

  /**
   * Returns the plural label for items being created, updated, or deleted.
   *
   * @return string
   *   The plural item label.
   */
  public function getItemLabelPlural() {
    if (!$this->entityType->getKey('bundle') || !$this->entityType->getBundleEntityType()) {
      return $this->entityTypeLabelPlural();
    }
    // Entity bundles do not support plural labels yet.
    // @todo Fix after https://www.drupal.org/project/drupal/issues/2765065.
    $storage = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType());
    $label = $storage->load($this->configuration['values'][$this->entityType->getKey('bundle')])->label();
    return $this->t('@label items', [
      '@label' => $label,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function newEntity(FeedInterface $feed) {
    $values = $this->configuration['values'];
    $entity = $this->storageController->create($values);
    $entity->enforceIsNew();

    if ($entity instanceof EntityOwnerInterface) {
      if ($this->configuration['owner_feed_author']) {
        $entity->setOwnerId($feed->getOwnerId());
      }
      else {
        $entity->setOwnerId($this->configuration['owner_id']);
      }
    }

    // Set language if the entity type has a field for it.
    if ($this->entityType->hasKey('langcode')) {
      $entity->{$this->entityType->getKey('langcode')} = $this->entityLanguage();
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTranslation(FeedInterface $feed, TranslatableInterface $entity, $langcode) {
    if (!$entity->hasTranslation($langcode)) {
      $translation = $entity->addTranslation($langcode);
      if ($translation instanceof EntityOwnerInterface) {
        if ($this->configuration['owner_feed_author']) {
          $translation->setOwnerId($feed->getOwnerId());
        }
        else {
          $translation->setOwnerId($this->configuration['owner_id']);
        }
      }

      return $translation;
    }

    return $entity->getTranslation($langcode);
  }

  /**
   * Checks if the entity exists already.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity already exists, false otherwise.
   */
  protected function entityExists(EntityInterface $entity) {
    if ($entity->id()) {
      $result = $this->storageController
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition($this->entityType->getKey('id'), $entity->id())
        ->execute();
      return !empty($result);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityValidate(EntityInterface $entity, FeedInterface $feed) {
    // Check if an entity with the same ID already exists if the given entity is
    // new.
    if ($entity->isNew() && $this->entityExists($entity)) {
      throw new ValidationException($this->t('An entity with ID %id already exists.', [
        '%id' => $entity->id(),
      ]));
    }

    $violations = $entity->validate();
    if (!count($violations)) {
      return;
    }

    $errors = [];

    foreach ($violations as $violation) {
      $error = $violation->getMessage();

      // Try to add more context to the message.
      // @todo if an exception occurred because of a different bundle, add more
      // context to the message.
      $invalid_value = $violation->getInvalidValue();
      if ($invalid_value instanceof FieldItemListInterface) {
        // The invalid value is a field. Get more information about this field.
        $error = new FormattableMarkup('@name (@property_name): @error', [
          '@name' => $invalid_value->getFieldDefinition()->getLabel(),
          '@property_name' => $violation->getPropertyPath(),
          '@error' => $error,
        ]);
      }
      else {
        $error = new FormattableMarkup('@property_name: @error', [
          '@property_name' => $violation->getPropertyPath(),
          '@error' => $error,
        ]);
      }

      $errors[] = $error;
    }

    $element = [
      '#theme' => 'item_list',
      '#items' => $errors,
    ];

    $entity_details = $this->identifyEntity($entity, $feed);
    $messages = [];
    $args = [
      '@entity' => mb_strtolower($this->entityTypeLabel()),
      '%label' => $entity_details['label'] ?? '',
      '@id' => $entity_details['id'] ?? '',
      '@errors' => $this->renderer->renderRoot($element),
      ':url' => $this->url('entity.feeds_feed_type.mapping', ['feeds_feed_type' => $this->feedType->id()]),
    ];

    if (empty($entity_details['type'])) {
      $messages[] = $this->t('An entity of type "@entity" failed to validate with the following errors: @errors', $args);
    }
    else {
      switch ($entity_details['type']) {
        case 'label':
          if ($entity_details['id'] || $entity_details['id'] === '0') {
            $messages[] = $this->t('The @entity %label (@id) failed to validate with the following errors: @errors', $args);
          }
          else {
            $messages[] = $this->t('The @entity %label failed to validate with the following errors: @errors', $args);
          }
          break;

        case 'guid':
          if ($entity_details['id'] || $entity_details['id'] === '0') {
            $messages[] = $this->t('The @entity with GUID %label (@id) failed to validate with the following errors: @errors', $args);
          }
          else {
            $messages[] = $this->t('The @entity with GUID %label failed to validate with the following errors: @errors', $args);
          }
          break;

        case 'id':
          $messages[] = $this->t('The @entity with ID %label failed to validate with the following errors: @errors', $args);
          break;
      }
    }
    $messages[] = $this->t('Please check your <a href=":url">mappings</a>.', $args);

    // Concatenate strings as markup to mark them as safe.
    $message_element = [
      '#markup' => implode("\n", $messages),
    ];
    $message = $this->renderer->renderRoot($message_element);

    throw new ValidationException($message);
  }

  /**
   * Tries to identify the entity, even if it is new.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to identify.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed that is importing the entity.
   *
   * @return array|null
   *   An array with the following data (in this order):
   *   - label (string): the label of the entity.
   *   - type (string): the type of identification.
   *   - id (int): the entity ID, if there is one.
   *   Or null if the entity couldn't be identified.
   */
  protected function identifyEntity(EntityInterface $entity, FeedInterface $feed): ?array {
    $identify_keys = [
      'label',
      'guid',
      'id',
    ];
    foreach ($identify_keys as $key) {
      switch ($key) {
        case 'label':
          $label = (string) $entity->label();
          break;

        case 'guid':
          if ($entity->hasField('feeds_item')) {
            $label = (string) $entity->get('feeds_item')->getItemByFeed($feed)->guid;
          }
          else {
            $label = NULL;
          }
          break;

        case 'id':
          $label = (string) $entity->id();
          break;
      }
      if ($label || $label === '0') {
        // An identification method has worked. Break out of the loop.
        break;
      }
    }

    if ($label || $label === '0') {
      return [
        'label' => $label,
        'type' => $key,
        'id' => $entity->id(),
      ];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function entitySaveAccess(EntityInterface $entity) {
    // No need to authorize.
    if (!$this->configuration['authorize'] || !$entity instanceof EntityOwnerInterface) {
      return;
    }

    // If the uid was mapped directly, rather than by email or username, it
    // could be invalid.
    $account = $entity->getOwner();
    if (!$account) {
      $owner_id = $entity->getOwnerId();
      if ($owner_id == 0) {
        // We don't check access for anonymous users.
        return;
      }

      throw new EntityAccessException($this->t('Invalid user with ID %uid mapped to %label.', [
        '%uid' => $owner_id,
        '%label' => $entity->label(),
      ]));
    }

    // We don't check access for anonymous users.
    if ($account->isAnonymous()) {
      return;
    }

    $op = $entity->isNew() ? 'create' : 'update';

    // Access granted.
    if ($entity->access($op, $account)) {
      return;
    }

    $args = [
      '%name' => $account->getDisplayName(),
      '@op' => $op,
      '@bundle' => $this->getItemLabelPlural(),
    ];
    throw new EntityAccessException($this->t('User %name is not authorized to @op @bundle.', $args));
  }

  /**
   * {@inheritdoc}
   */
  public function entityLanguage() {
    $langcodes = $this->languageOptions();

    if (isset($this->configuration['langcode']) && isset($langcodes[$this->configuration['langcode']])) {
      return $this->configuration['langcode'];
    }

    // Return default language.
    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  protected function entityDeleteMultiple(array $entity_ids) {
    $entities = $this->storageController->loadMultiple($entity_ids);
    $this->storageController->delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'insert_new' => static::INSERT_NEW,
      'update_existing' => static::SKIP_EXISTING,
      'update_non_existent' => static::KEEP_NON_EXISTENT,
      'skip_hash_check' => FALSE,
      'values' => [],
      'authorize' => $this->entityType->entityClassImplements('Drupal\user\EntityOwnerInterface'),
      'revision' => FALSE,
      'expire' => static::EXPIRE_NEVER,
      'owner_id' => 0,
      'owner_feed_author' => 0,
    ];

    // Bundle.
    if ($bundle_key = $this->entityType->getKey('bundle')) {
      $defaults['values'] = [$bundle_key => NULL];
    }

    // Language.
    if ($langcode_key = $this->entityType->getKey('langcode')) {
      $defaults['langcode'] = $this->languageManager->getDefaultLanguage()->getId();
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedTypeSave($update = TRUE) {
    $this->prepareFeedsItemField();
  }

  /**
   * {@inheritdoc}
   */
  public function onFeedTypeDelete() {
    $this->removeFeedItemField();
  }

  /**
   * Prepares the feeds_item field.
   *
   * @todo How does ::load() behave for deleted fields?
   */
  protected function prepareFeedsItemField() {
    // Do not create field when syncing configuration.
    if (\Drupal::isConfigSyncing()) {
      return FALSE;
    }
    // Create field if it doesn't exist.
    if (!FieldStorageConfig::loadByName($this->entityType(), 'feeds_item')) {
      FieldStorageConfig::create([
        'field_name' => 'feeds_item',
        'entity_type' => $this->entityType(),
        'type' => 'feeds_item',
        'translatable' => FALSE,
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ])->save();
    }
    // Create field instance if it doesn't exist.
    if (!FieldConfig::loadByName($this->entityType(), $this->bundle(), 'feeds_item')) {
      FieldConfig::create([
        'label' => 'Feeds item',
        'description' => '',
        'field_name' => 'feeds_item',
        'entity_type' => $this->entityType(),
        'bundle' => $this->bundle(),
      ])->save();
    }
  }

  /**
   * Deletes the feeds_item field.
   */
  protected function removeFeedItemField() {
    $storage_in_use = FALSE;
    $instance_in_use = FALSE;

    foreach (FeedType::loadMultiple() as $feed_type) {
      if ($feed_type->id() === $this->feedType->id()) {
        continue;
      }
      $processor = $feed_type->getProcessor();
      if (!$processor instanceof EntityProcessorInterface) {
        continue;
      }

      if ($processor->entityType() === $this->entityType()) {
        $storage_in_use = TRUE;

        if ($processor->bundle() === $this->bundle()) {
          $instance_in_use = TRUE;
          break;
        }
      }
    }

    if ($instance_in_use) {
      return;
    }

    // Delete the field instance.
    if ($config = FieldConfig::loadByName($this->entityType(), $this->bundle(), 'feeds_item')) {
      $config->delete();
    }

    if ($storage_in_use) {
      return;
    }

    // Delte the field storage.
    if ($storage = FieldStorageConfig::loadByName($this->entityType(), 'feeds_item')) {
      $storage->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function expiryTime() {
    return $this->configuration['expire'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiredIds(FeedInterface $feed, $time = NULL) {
    if ($time === NULL) {
      $time = $this->expiryTime();
    }
    if ($time == static::EXPIRE_NEVER) {
      return;
    }
    $expire_time = $this->dateTime->getRequestTime() - $time;
    return $this->entityTypeManager
      ->getStorage($this->entityType())
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('feeds_item.target_id', $feed->id())
      ->condition('feeds_item.imported', $expire_time, '<')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function expireItem(FeedInterface $feed, $item_id, StateInterface $state) {
    $this->entityDeleteMultiple([$item_id]);
    $state->total++;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount(FeedInterface $feed) {
    return $this->entityTypeManager
      ->getStorage($this->entityType())
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('feeds_item.target_id', $feed->id())
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportedItemIds(FeedInterface $feed) {
    return $this->entityTypeManager
      ->getStorage($this->entityType())
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('feeds_item.target_id', $feed->id())
      ->execute();
  }

  /**
   * Returns an existing entity id.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to find existing ids for.
   *
   * @return int|string|null
   *   The ID of the entity, or null if not found.
   */
  protected function existingEntityId(FeedInterface $feed, ItemInterface $item) {
    foreach ($this->feedType->getMappings() as $delta => $mapping) {
      if (empty($mapping['unique'])) {
        continue;
      }

      foreach ($mapping['unique'] as $key => $true) {
        $plugin = $this->feedType->getTargetPlugin($delta);
        $entity_id = $plugin->getUniqueValue($feed, $mapping['target'], $key, $item->get($mapping['map'][$key]));
        if ($entity_id) {
          return $entity_id;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildAdvancedForm(array $form, FormStateInterface $form_state) {
    if ($bundle_key = $this->entityType->getKey('bundle')) {
      $form['values'][$bundle_key] = [
        '#type' => 'select',
        '#options' => $this->bundleOptions(),
        '#title' => $this->bundleLabel(),
        '#required' => TRUE,
        '#default_value' => $this->bundle() ?: key($this->bundleOptions()),
        '#disabled' => $this->isLocked(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state) {
    $added_target = $form_state->getValue('add_target');
    if (!$added_target) {
      // No target was added this time around. Abort.
      return;
    }

    // When adding a mapping target to entity ID, tick 'unique' by default.
    $id_key = $this->entityType->getKey('id');

    $mappings = $this->feedType->getMappings();
    $last_delta = array_keys($mappings)[count($mappings) - 1];
    $mapping = end($mappings);

    if ($mapping['target'] != $added_target) {
      return;
    }

    $target_definition = $this->feedType->getTargetPlugin($last_delta)
      ->getTargetDefinition();
    if (!$target_definition instanceof FieldTargetDefinition) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $target_definition->getFieldDefinition();
    if ($field_definition->getName() != $id_key) {
      return;
    }

    // We made it! Set property as unique.
    $form['mappings'][$last_delta]['unique'][$field_definition->getMainPropertyName()]['#default_value'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state) {
    // Display a warning when mapping to entity ID and having that one not set
    // as unique.
    $id_key = $this->entityType->getKey('id');
    foreach ($this->feedType->getMappings() as $delta => $mapping) {
      try {
        $target_definition = $this->feedType->getTargetPlugin($delta)
          ->getTargetDefinition();
        if (!$target_definition instanceof FieldTargetDefinition) {
          continue;
        }

        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        $field_definition = $target_definition->getFieldDefinition();
        if ($field_definition->getName() != $id_key) {
          continue;
        }

        $is_unique = $form_state->getValue([
          'mappings',
          $delta,
          'unique',
          $field_definition->getMainPropertyName(),
        ]);
        if (!$is_unique) {
          // Entity ID not set as unique. Display warning.
          $this->messenger()->addWarning($this->t('When mapping to the entity ID (@name), it is recommended to set it as unique.', [
            '@name' => $target_definition->getLabel(),
          ]));
        }
      }
      catch (MissingTargetException $e) {
        // Ignore missing targets.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state) {
    // The entity processor doesn't have to do anything when mappings are saved.
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    if ($this->isLocked === NULL) {
      // Look for feeds.
      $this->isLocked = (bool) $this->entityTypeManager
        ->getStorage('feeds_feed')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $this->feedType->id())
        ->range(0, 1)
        ->execute();
    }

    return $this->isLocked;
  }

  /**
   * Creates an MD5 hash of an item.
   *
   * Includes mappings so that items will be updated if the mapping
   * configuration has changed.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to hash.
   *
   * @return string
   *   An MD5 hash.
   */
  protected function hash(ItemInterface $item) {
    $sources = $this->feedType->getMappedSources();
    $mapped_item = array_intersect_key($item->toArray(), $sources);
    return hash('md5', serialize($mapped_item) . serialize($this->feedType->getMappings()));
  }

  /**
   * Execute mapping on an item.
   *
   * This method encapsulates the central mapping functionality. When an item is
   * processed, it is passed through map() where the properties of $source_item
   * are mapped onto $target_item following the processor's mapping
   * configuration.
   */
  protected function map(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    $mappings = $this->feedType->getMappings();

    // Mappers add to existing fields rather than replacing them. Hence we need
    // to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($mappings as $delta => $mapping) {
      if ($mapping['target'] == 'feeds_item') {
        // Skip feeds item as this field gets default values before mapping.
        continue;
      }

      // Clear the target.
      $this->clearTarget($entity, $this->feedType->getTargetPlugin($delta), $mapping['target']);
    }

    // Gather all of the values for this item.
    $source_values = [];
    foreach ($mappings as $delta => $mapping) {
      $target = $mapping['target'];

      foreach ($mapping['map'] as $column => $source) {

        if ($source === '') {
          // Skip empty sources.
          continue;
        }

        if (!isset($source_values[$delta][$column])) {
          $source_values[$delta][$column] = [];
        }

        $value = $item->get($source);
        if (!is_array($value)) {
          $source_values[$delta][$column][] = $value;
        }
        else {
          $source_values[$delta][$column] = array_merge($source_values[$delta][$column], $value);
        }
      }
    }

    // Rearrange values into Drupal's field structure.
    $field_values = [];
    foreach ($source_values as $field => $field_value) {
      $field_values[$field] = [];
      foreach ($field_value as $column => $values) {
        // Use array_values() here to keep our $delta clean.
        foreach (array_values($values) as $delta => $value) {
          $field_values[$field][$delta][$column] = $value;
        }
      }
    }

    // Set target values.
    foreach ($mappings as $delta => $mapping) {
      $plugin = $this->feedType->getTargetPlugin($delta);

      // Skip immutable targets for which the entity already has a value.
      if (!$plugin->isMutable() && !$plugin->isEmpty($feed, $entity, $mapping['target'])) {
        continue;
      }

      if (isset($field_values[$delta])) {
        $plugin->setTarget($feed, $entity, $mapping['target'], $field_values[$delta]);
      }
    }

    return $entity;
  }

  /**
   * Clears the target on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to clear the target on.
   * @param \Drupal\feeds\Plugin\Type\Target\TargetInterface $target
   *   The target plugin.
   * @param string $target_name
   *   The property to clear on the entity.
   */
  protected function clearTarget(EntityInterface $entity, TargetInterface $target, $target_name) {
    if (!$target->isMutable()) {
      // Don't clear immutable targets.
      return;
    }

    $entity_target = $entity;

    // If the target implements TranslatableTargetInterface and has a language
    // configured, empty the value for the targeted language only.
    // In all other cases, empty the target for the entity in the default
    // language or just the whole target if the entity isn't translatable.
    if ($entity instanceof TranslatableInterface && $target instanceof TranslatableTargetInterface && $entity->isTranslatable()) {
      // We expect the target to return a langcode. If it doesn't return one, we
      // expect that the target for the entity in the default language must be
      // emptied.
      $langcode = $target->getLangcode();
      if ($langcode) {
        // Langcode exists, check if the entity is available in that language.
        if ($entity->hasTranslation($langcode)) {
          $entity_target = $entity->getTranslation($langcode);
        }
        else {
          // Entity hasn't got a translation in the given langcode yet, so we
          // don't need to empty anything.
          return;
        }
      }
    }

    unset($entity_target->{$target_name});
  }

  /**
   * {@inheritdoc}
   *
   * @todo Avoid using the database service. Find an other way to clean up
   * references to feeds that are being removed.
   * @todo the cache clearing logic of target entity could probably be addressed
   * along with the todo above.
   */
  public function onFeedDeleteMultiple(array $feeds) {
    $fids = [];
    foreach ($feeds as $feed) {
      $fids[] = $feed->id();
    }

    $entity_type_id = $this->entityType();
    $table = "{$entity_type_id}__feeds_item";

    // Clear the cache of associated target entities so that they won't
    // reference to the deleted feeds items.
    $target_entities = $this->database->select($table, 'fi')
      ->condition('feeds_item_target_id', $fids, 'IN')
      ->fields('fi', ['entity_id'])
      ->execute()
      ->fetchCol();

    $unique_ids = array_unique($target_entities);
    $this->entityTypeManager->getStorage($entity_type_id)->resetCache($unique_ids);

    $this->database->delete($table)
      ->condition('feeds_item_target_id', $fids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Add dependency on entity type.
    $entity_type = $this->entityTypeManager->getDefinition($this->entityType());
    $this->addDependency('module', $entity_type->getProvider());

    // Add dependency on entity bundle.
    if ($this->bundle()) {
      $bundle_dependency = $entity_type->getBundleConfigDependency($this->bundle());
      $this->addDependency($bundle_dependency['type'], $bundle_dependency['name']);
    }

    // For the 'update_non_existent' setting, add dependency on selected action.
    switch ($this->getConfiguration('update_non_existent')) {
      case static::KEEP_NON_EXISTENT:
      case static::DELETE_NON_EXISTENT:
        // No dependency to add.
        break;

      default:
        try {
          $definition = $this->actionManager->getDefinition($this->getConfiguration('update_non_existent'));
          if (isset($definition['provider'])) {
            $this->addDependency('module', $definition['provider']);
          }
        }
        catch (PluginNotFoundException $e) {
          // It's possible that the selected action plugin no longer exists. Log
          // an error about it.
          $this->logger->warning('The selected option for the setting "Previously imported items" in the feed type %feed_type_id no longer exists. Please edit the feed type and select a different option for that setting.', [
            '%feed_type_id' => $this->feedType->id(),
          ]);
        }
        break;
    }

    return $this->dependencies;
  }

}
