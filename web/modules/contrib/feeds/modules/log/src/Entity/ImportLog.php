<?php

namespace Drupal\feeds_log\Entity;

use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds_log\ImportLogInterface;

/**
 * Defines the import log entity class.
 *
 * @ContentEntityType(
 *   id = "feeds_import_log",
 *   label = @Translation("Feeds Import Log"),
 *   label_singular = @Translation("logged import"),
 *   label_plural = @Translation("logged imports"),
 *   label_count = @PluralTranslation(
 *     singular = "@count feeds import log",
 *     plural = "@count feeds import logs",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\feeds_log\LogListBuilder",
 *     "storage" = "Drupal\feeds_log\LogStorage",
 *     "view_builder" = "Drupal\feeds_log\LogViewBuilder",
 *     "access" = "Drupal\feeds_log\FeedsLogAccessControlHandler",
 *     "views_data" = "Drupal\feeds_log\LogViewsData",
 *     "form" = {
 *       "delete" = "Drupal\feeds_log\Form\DeleteForm",
 *     },
 *   },
 *   base_table = "feeds_import_log",
 *   entity_keys = {
 *     "id" = "import_id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/feed/{feeds_feed}/log/{feeds_import_log}",
 *     "delete-form" = "/feed/{feeds_feed}/log/{feeds_import_log}/delete",
 *   }
 * )
 */
class ImportLog extends ContentEntityBase implements ImportLogInterface {

  /**
   * {@inheritdoc}
   */
  public function logSource(FetcherResultInterface $result): string {
    $file_manager = $this->getFeedsLogFileManager();
    $file_path = \Drupal::service('file_system')->basename($result->getFilePath());
    $destination = $file_manager->saveData($result->getRaw(), $this->id() . '/source/' . $file_path);
    $this->sources[] = $destination;

    return $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function logItem(ItemInterface $item, $index = 0): string {
    $file_manager = $this->getFeedsLogFileManager();
    $destination = $file_manager->saveData(json_encode($item->toArray()), $this->id() . '/items/' . $index . '.json');

    return $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function addLogEntry(array &$entry = []) {
    $database = \Drupal::database();

    $entry['import_id'] = $this->id();
    $entry['feed_id'] = $this->feed->target_id;

    $this->sanitizeLogEntry($entry);

    $index = $database->insert(static::ENTRY_TABLE)
      ->fields($entry)
      ->execute();
    $entry['lid'] = $index;

    return $index;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLogEntry(array &$entry) {
    $database = \Drupal::database();

    $entry['import_id'] = $this->id();
    $entry['feed_id'] = $this->feed->target_id;

    $this->sanitizeLogEntry($entry);

    return $database->update(static::ENTRY_TABLE)
      ->condition('lid', $entry['lid'])
      ->fields($entry)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedLabel(): string {
    $feeds = $this->feed->referencedEntities();
    $feed = reset($feeds);
    return $feed->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getImportStartTime(): ?int {
    return $this->start->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportFinishTime(): ?int {
    return $this->end->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSources(): array {
    $return = [];
    foreach ($this->sources as $source) {
      $return[] = $source->value;
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(array $options = []): SelectInterface {
    $database = \Drupal::database();

    // Default options.
    $options += [
      'header' => NULL,
      'limit' => NULL,
    ];

    $query = $database->select(static::ENTRY_TABLE, 'log')
      ->fields('log', [
        'entity_id',
        'entity_type_id',
        'entity_label',
        'item',
        'item_id',
        'operation',
        'message',
        'variables',
        'timestamp',
      ])
      ->condition('import_id', $this->id())
      ->condition('feed_id', $this->feed->target_id);

    // Set header sorting.
    if ($options['header']) {
      $query = $query->extend(TableSortExtender::class)
        ->orderByHeader($options['header']);
    }

    // Set limit pager.
    if ($options['limit']) {
      $query = $query->extend(PagerSelectExtender::class)
        ->limit($options['limit']);
    }

    if (!empty($options['conditions'])) {
      foreach ($options['conditions'] as $key => $condition) {
        if (empty($condition['operator'])) {
          $condition['operator'] = '=';
        }
        $query->condition($key, $condition['value'], $condition['operator']);
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogEntries(array $options = []): array {
    $result = $this->getQuery($options)->execute();
    $records = [];
    while ($record = $result->fetchObject()) {
      $record->variables = unserialize($record->variables);
      $records[] = $record;
    }
    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $ids = array_keys($entities);

    // Clean up logs in feeds_import_log_entry table for each deleted log
    // entity.
    \Drupal::database()->delete('feeds_import_log_entry')
      ->condition('import_id', $ids, 'IN')
      ->execute();

    /** @var \Drupal\feeds_log\LogFileManagerInterface $log_file_manager */
    $log_file_manager = \Drupal::service('feeds_log.file_manager');
    foreach ($ids as $import_log_id) {
      $log_file_manager->removeFiles($import_log_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['feed'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Feed'))
      ->setDescription(t('The feed that this log belongs to.'))
      ->setSetting('target_type', 'feeds_feed')
      ->setSetting('handler', 'default');

    $fields['start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Import start time'))
      ->setDescription(t('The time when the import started.'));

    $fields['end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Import finish time'))
      ->setDescription(t('The time when the import finished.'));

    $fields['sources'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Sources'))
      ->setDescription(t('Reference to the logged source files.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user for the log.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\feeds_log\Entity\ImportLog::getCurrentUserId');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the log was created.'));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    // Add feeds_feed to route parameters.
    $uri_route_parameters['feeds_feed'] = $this->feed->target_id;

    return $uri_route_parameters;
  }

  /**
   * Returns the feeds log file manager service.
   *
   * @return \Drupal\feeds_log\LogFileManagerInterface
   *   The Feeds log file manager.
   */
  protected function getFeedsLogFileManager() {
    return \Drupal::service('feeds_log.file_manager');
  }

  /**
   * Sanitizes the input before being added/updated to the database.
   *
   * @param array $entry
   *   The log entry.
   */
  protected function sanitizeLogEntry(array &$entry) {
    $entry += [
      'entity_id' => 0,
      'entity_type_id' => '',
      'entity_label' => '',
      'operation' => '',
      'message' => '',
      'variables' => serialize([]),
    ];

    $entry['entity_type_id'] = mb_substr($entry['entity_type_id'], 0, 32);
    $entry['entity_label'] = mb_substr($entry['entity_label'], 0, 255);
    if (isset($entry['item'])) {
      $entry['item'] = mb_substr($entry['item'], 0, 255);
    }
    if (isset($entry['item_id'])) {
      $entry['item_id'] = mb_substr($entry['item_id'], 0, 255);
    }
    $entry['operation'] = mb_substr($entry['operation'], 0, 64);
  }

}
