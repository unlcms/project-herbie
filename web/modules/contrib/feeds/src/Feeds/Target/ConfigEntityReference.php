<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
 * Defines a config entity reference mapper.
 *
 * @FeedsTarget(
 *   id = "config_entity_reference",
 *   field_types = {"entity_reference"},
 * )
 */
class ConfigEntityReference extends FieldTargetBase implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Feeds entity finder service.
   *
   * @var \Drupal\feeds\EntityFinderInterface
   */
  protected $entityFinder;

  /**
   * The transliteration manager.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The manager for managing config schema type plugins.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a ConfigEntityReference object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\feeds\EntityFinderInterface $entity_finder
   *   The Feeds entity finder service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The manager for managing config schema type plugins.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFinderInterface $entity_finder, TransliterationInterface $transliteration, TypedConfigManagerInterface $typed_config_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFinder = $entity_finder;
    $this->transliteration = $transliteration;
    $this->typedConfigManager = $typed_config_manager;
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
      $container->get('feeds.entity_finder'),
      $container->get('transliteration'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $type = $field_definition->getSetting('target_type');
    if (!\Drupal::entityTypeManager()->getDefinition($type)->entityClassImplements(ConfigEntityInterface::class)) {
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
   * Returns possible fields to reference by for a config entity.
   *
   * @return array
   *   A list of fields to reference by.
   */
  protected function getPotentialFields() {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $config_entity_type */
    $config_entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $config_name = $config_entity_type->getConfigPrefix() . '.*';
    $definition = $this->typedConfigManager->getDefinition($config_name);

    if (!empty($definition['mapping'])) {
      $options = [];
      foreach ($definition['mapping'] as $key => $mapper) {
        switch ($mapper['type']) {
          case 'integer':
          case 'label':
          case 'string':
          case 'text':
          case 'uuid':
            $options[$key] = $mapper['label'];
            break;
        }
      }
      return $options;
    }

    return [
      'id' => $this->t('ID'),
      'uuid' => $this->t('UUID'),
    ];
  }

  /**
   * Returns the entity type that can be referenced.
   *
   * @return string
   *   The entity type being referenced.
   */
  protected function getEntityType() {
    return $this->settings['target_type'];
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

    if ($target_id = $this->findEntity($this->configuration['reference_by'], $values['target_id'])) {
      $values['target_id'] = $target_id;
      return;
    }

    throw new ReferenceNotFoundException($this->t('Referenced entity not found for field %field with value %target_id.', [
      '%target_id' => $values['target_id'],
      '%field' => $this->configuration['reference_by'],
    ]));
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
    $ids = $this->entityFinder->findEntities($this->getEntityType(), $field, $search);
    if ($ids) {
      return reset($ids);
    }

    return FALSE;
  }

  /**
   * Generates a machine name from a string.
   *
   * This is basically the same as what is done in
   * \Drupal\Core\Block\BlockBase::getMachineNameSuggestion() and
   * \Drupal\system\MachineNameController::transliterate(), but it seems
   * that so far there is no common service for handling this.
   *
   * @param string $string
   *   The string to generate a machine name for.
   *
   * @return string
   *   The generated machine name.
   *
   * @see \Drupal\Core\Block\BlockBase::getMachineNameSuggestion()
   * @see \Drupal\system\MachineNameController::transliterate()
   */
  protected function generateMachineName($string) {
    $transliterated = $this->transliteration->transliterate($string, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = mb_strtolower($transliterated);

    $transliterated = preg_replace('@[^a-z0-9_.]+@', '_', $transliterated);

    return $transliterated;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'reference_by' => 'id',
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = $this->getPotentialFields();

    // Hack to find out the target delta.
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $options = $this->getPotentialFields();

    $summary = [];

    if ($this->configuration['reference_by'] && isset($options[$this->configuration['reference_by']])) {
      $summary[] = $this->t('Reference by: %message', ['%message' => $options[$this->configuration['reference_by']]]);
    }
    else {
      $summary[] = $this->t('Please select a field to reference by.');
    }

    return $summary;
  }

}
