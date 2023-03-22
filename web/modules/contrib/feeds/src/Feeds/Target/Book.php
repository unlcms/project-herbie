<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\book\BookManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\feeds\EntityFinderInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\TargetBase;
use Drupal\feeds\StateInterface;
use Drupal\feeds\TargetDefinition;
use Drupal\node\NodeStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a mapper to book properties.
 *
 * @FeedsTarget(
 *   id = "book"
 * )
 */
class Book extends TargetBase implements ConfigurableTargetInterface, ContainerFactoryPluginInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * The Feeds entity finder service.
   *
   * @var \Drupal\feeds\EntityFinderInterface
   */
  protected $entityFinder;

  /**
   * Constructs a new Book object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   * @param \Drupal\feeds\EntityFinderInterface $entity_finder
   *   The Feeds entity finder service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $database, NodeStorageInterface $node_storage, EntityFieldManagerInterface $entity_field_manager, BookManagerInterface $book_manager, EntityFinderInterface $entity_finder) {
    $this->database = $database;
    $this->nodeStorage = $node_storage;
    $this->entityFieldManager = $entity_field_manager;
    $this->bookManager = $book_manager;
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
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('entity_field.manager'),
      $container->get('book.manager'),
      $container->get('feeds.entity_finder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition) {
    // Don't show mapping target to book, if there is none.
    if (!\Drupal::moduleHandler()->moduleExists('book')) {
      return;
    }

    $processor = $feed_type->getProcessor();

    if (!$processor instanceof EntityProcessorInterface) {
      return $targets;
    }

    // The book module solely works with nodes.
    if ($processor->entityType() != 'node') {
      return $targets;
    }

    if ($target = static::prepareTarget()) {
      $target->setPluginId($definition['id']);
      $targets['book'] = $target;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget() {
    $description = new FormattableMarkup('@1<br />@2<br />@3', [
      '@1' => t('Allows you to include pages in a Book hierarchy.'),
      '@2' => t('Mapping to subtarget "@bid" is optional, just specifying "@pid" is enough to get the imported node in a book as well.', [
        '@bid' => t('Book'),
        '@pid' => t('Parent item'),
      ]),
      '@3' => t('When the value for "@new" is "1", Feeds attempts to create a new book if the imported node in question is not already in a book.', [
        '@new' => t('Is top-level page'),
      ]),
    ]);

    return TargetDefinition::create()
      ->setLabel(t('Book outline'))
      ->addProperty('bid', t('Book'))
      ->addProperty('pid', t('Parent item'))
      ->addProperty('weight', t('Weight'))
      ->addProperty('new', t('Is top-level page'))
      ->setDescription($description);
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $raw_values) {
    $values = [];
    try {
      $values = $this->prepareValues($raw_values);
    }
    catch (ReferenceNotFoundException $e) {
      // The referenced entity is not found. We need to enforce Feeds to try
      // to import the same item again on the next import.
      // Feeds stores a hash of every imported item in order to make the
      // import process more efficient by ignoring items it has already seen.
      // In this case we need to destroy the hash in order to be able to
      // import the reference on a next import.
      $entity->get('feeds_item')->hash = NULL;
      $feed->getState(StateInterface::PROCESS)->setMessage($e->getFormattedMessage(), 'warning', TRUE);
    }
    catch (EmptyFeedException $e) {
      // Nothing wrong here.
    }
    catch (TargetValidationException $e) {
      // Validation failed.
      $this->addMessage($e->getFormattedMessage(), 'error');
    }

    $book = [];
    // Get original book values when updating the node.
    if (!$entity->isNew()) {
      $original = $this->nodeStorage->loadUnchanged($entity->id());
      if (!empty($original->book)) {
        $book = $original->book;
      }

      // If 'bid' is set to 'new', set it to the node ID instead.
      if (isset($values['bid']) && $values['bid'] == 'new') {
        $values['bid'] = $entity->id();
      }
    }

    // Remove an existing node from book when the values are empty.
    if (empty($values)) {
      if (!$entity->isNew()) {
        if ($this->bookManager->checkNodeIsRemovable($original)) {
          $this->bookManager->deleteFromBook($entity->id());
        }
      }

      // Stop here, since no values should have been set.
      return;
    }

    // Merge the new values with the original book values.
    $entity->book = $values + $book;
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
    return 'node';
  }

  /**
   * Prepares the values that will be mapped to an entity.
   *
   * @param array $raw_values
   *   The values.
   *
   * @return array
   *   The altered values.
   */
  protected function prepareValues(array $raw_values) {
    $values = reset($raw_values);

    // Lookup entities.
    if (!empty($values['bid'])) {
      $values['bid'] = $this->findEntity('book', $values['bid']);
    }
    if (!empty($values['pid'])) {
      $bid = $values['bid'] ?? NULL;
      $values['pid'] = $this->findEntity('parent', $values['pid'], $bid);
    }

    // When there is no value for book ID, but there is for parent, grab the
    // book ID from the parent item.
    if (empty($values['bid']) && !empty($values['pid'])) {
      // Grab book ID from parent.
      $parent = $this->nodeStorage->load($values['pid']);
      if ($parent && !empty($parent->book['bid'])) {
        $values['bid'] = $parent->book['bid'];
      }
    }

    // If the book is configured to be the top level page, set 'bid'
    // to "new" if no values have been set so far.
    if (empty($values['bid']) && !empty($values['new'])) {
      return [
        'bid' => 'new',
      ];
    }

    // If there is still no book ID, then return nothing.
    if (empty($values['bid'])) {
      return [];
    }
    elseif (empty($values['pid'])) {
      // Set parent ID the same as the book ID when no parent has been given.
      $values['pid'] = $values['bid'];
    }

    return $values;
  }

  /**
   * Tries to lookup an existing entity.
   *
   * @param string $reference_prefix
   *   The prefix for the reference setting.
   * @param string|int $search
   *   The value to lookup.
   * @param int $book_id
   *   The book ID to restrict the search for.
   *
   * @return int
   *   The ID of the entity.
   */
  protected function findEntity(string $reference_prefix, $search, $book_id = NULL) {
    $field = $this->configuration[$reference_prefix . '_reference_by'];
    if ($field == 'feeds_item') {
      $field = 'feeds_item.' . $this->configuration[$reference_prefix . '_feeds_item'];
    }

    // The query that the entity finder service executes cannot restrict the
    // search by book ID. Therefore we must lookup multiple results and filter
    // by book ID later. See the call to ::findFirstNidInBook() below.
    $multiple = $book_id ? TRUE : FALSE;
    $target_ids = $this->entityFinder->findEntities($this->getEntityType(), $field, $search, [], $multiple);

    // If a book is specified, search specifically within that book.
    if ($book_id && !empty($target_ids)) {
      $result = $this->findFirstNidInBook($book_id, $target_ids);
      if ($result) {
        return $result;
      }
    }
    elseif (!empty($target_ids)) {
      return reset($target_ids);
    }

    if ($book_id) {
      throw new ReferenceNotFoundException($this->t('Referenced entity not found for field %field with value %target_id in book with ID %bid.', [
        '%bid' => $book_id,
        '%target_id' => $search,
        '%field' => $this->configuration[$reference_prefix . '_reference_by'],
      ]));
    }
    else {
      throw new ReferenceNotFoundException($this->t('Referenced entity not found for field %field with value %target_id.', [
        '%target_id' => $search,
        '%field' => $this->configuration[$reference_prefix . '_reference_by'],
      ]));
    }
  }

  /**
   * Returns the first node ID that is in the given book.
   *
   * @param int $book_id
   *   The book to search within.
   * @param int[] $nids
   *   The node ID's to check.
   *
   * @return int|false
   *   The first node ID that appears in the given book or false if none of the
   *   passed node ID's are in the book.
   */
  protected function findFirstNidInBook(int $book_id, array $nids) {
    return $this->database->select('book', 'b')
      ->fields('b', ['nid'])
      ->condition('b.bid', $book_id)
      ->condition('b.nid', $nids, 'IN')
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function isMutable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(FeedInterface $feed, EntityInterface $entity, $target) {
    if (empty($entity->book['bid'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration() + [
      'book_reference_by' => 'nid',
      'parent_reference_by' => 'nid',
    ];
    if (array_key_exists('feeds_item', $this->getPotentialFields())) {
      $config['book_feeds_item'] = FALSE;
      $config['parent_feeds_item'] = FALSE;
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = $this->getPotentialFields();
    $feed_item_options = $this->getFeedsItemOptions();

    // Hack to find out the target delta.
    $delta = 0;
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'target-settings-') === 0) {
        list(, , $delta) = explode('-', $key);
        break;
      }
    }

    $form['book_reference_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference book by'),
      '#options' => $options,
      '#default_value' => $this->configuration['book_reference_by'],
    ];
    $form['book_feeds_item'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed item'),
      '#options' => $feed_item_options,
      '#default_value' => $this->getConfiguration('book_feeds_item'),
      '#states' => [
        'visible' => [
          ':input[name="mappings[' . $delta . '][settings][book_reference_by]"]' => [
            'value' => 'feeds_item',
          ],
        ],
      ],
    ];

    $form['parent_reference_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference parent item by'),
      '#options' => $options,
      '#default_value' => $this->configuration['parent_reference_by'],
    ];
    $form['parent_feeds_item'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed item'),
      '#options' => $feed_item_options,
      '#default_value' => $this->getConfiguration('parent_feeds_item'),
      '#states' => [
        'visible' => [
          ':input[name="mappings[' . $delta . '][settings][parent_reference_by]"]' => [
            'value' => 'feeds_item',
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

    $reference_prefixes = ['book', 'parent'];
    foreach ($reference_prefixes as $prefix) {
      $option_key = $this->configuration[$prefix . '_reference_by'];
      $option_label = $options[$option_key] ?? NULL;

      if ($option_key && $option_label) {
        switch ($prefix) {
          case 'book':
            $summary[] = $this->t('Reference book by: %message', ['%message' => $option_label]);
            break;

          case 'parent':
            $summary[] = $this->t('Reference parent item by: %message', ['%message' => $option_label]);
            break;
        }
      }
      if ($option_key == 'feeds_item') {
        $feed_item_options = $this->getFeedsItemOptions();
        $summary[] = $this->t('Feed item: %feed_item', ['%feed_item' => $feed_item_options[$this->configuration[$prefix . '_feeds_item']]]);
      }
    }

    return $summary;
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
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    // Add the book module as dependency.
    $this->dependencies['module'][] = 'book';

    return $this->dependencies;
  }

}
