<?php

namespace Drupal\feeds_log;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;

/**
 * Interface for import logs.
 */
interface ImportLogInterface extends ContentEntityInterface {

  /**
   * Name of the table where log entries are stored.
   */
  const ENTRY_TABLE = 'feeds_import_log_entry';

  /**
   * Logs the raw contents of the fetcher result to a file.
   *
   * @param \Drupal\feeds\Result\FetcherResultInterface $result
   *   The fetcher result to log.
   *
   * @return string
   *   The uri of the file that was logged.
   */
  public function logSource(FetcherResultInterface $result): string;

  /**
   * Logs the parsed item to a file.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to log.
   *
   * @return string
   *   The uri of the file that was logged.
   */
  public function logItem(ItemInterface $item): string;

  /**
   * Adds a new log entry to the log table.
   *
   * @param array $entry
   *   The entry to log which can contain the following:
   *   - entity_id (int)
   *     The ID of the entity that was involved. Can be empty if the entity
   *     failed to import.
   *   - entity_type_id (string)
   *     The type of the entity that was involved.
   *   - entity_label (string)
   *     An alternative for identifying the entity, because the entity may not
   *     exist yet or it may have been deleted.
   *   - item
   *     Uri of the logged item, if available.
   *   - operation (string)
   *     Type of the operation, for example "created", "updated", or "cleaned".
   *   - message
   *     Text of log message.
   *   - Serialized array of variables that match the message string and that is
   *     passed into the t() function.
   *   - timestamp
   *     Unix timestamp of when the event occurred.
   *
   * @return int
   *   The ID that the log entry received in the database.
   */
  public function addLogEntry(array &$entry = []);

  /**
   * Updates an existing log entry in the log table.
   *
   * @param array $entry
   *   The entry to log which can contain the following:
   *   - lid (int)
   *     Required. The ID of the log entry.
   *   - entity_id (int)
   *     The ID of the entity that was involved. Can be empty if the entity
   *     failed to import.
   *   - entity_type_id (string)
   *     The type of the entity that was involved.
   *   - entity_label (string)
   *     An alternative for identifying the entity, because the entity may not
   *     exist yet or it may have been deleted.
   *   - item
   *     Uri of the logged item, if available.
   *   - operation (string)
   *     Type of the operation, for example "created", "updated", or "cleaned".
   *   - message
   *     Text of log message.
   *   - Serialized array of variables that match the message string and that is
   *     passed into the t() function.
   *   - timestamp
   *     Unix timestamp of when the event occurred.
   */
  public function updateLogEntry(array &$entry);

  /**
   * Returns the label of the feed.
   *
   * @return string
   *   The feed label.
   */
  public function getFeedLabel(): string;

  /**
   * Returns a timestamp of when this import started.
   *
   * @return int|null
   *   Import start timestamp or null if the import was not started yet.
   */
  public function getImportStartTime(): ?int;

  /**
   * Returns a timestamp of when this import finished.
   *
   * @return int|null
   *   Finish timestamp or null if the import was not started yet.
   */
  public function getImportFinishTime(): ?int;

  /**
   * Returns a list of uri's of logged source files.
   *
   * @return string[]
   *   A list of uri's.
   */
  public function getSources(): array;

  /**
   * Returns a query builder for selecting log entries from the database.
   *
   * @param array $options
   *   (optional) Options for retrieving the entries:
   *   - header (string)
   *     The column to sort the entries by.
   *   - limit (int)
   *     The maximum number of entries to retrieve.
   *   - conditions (array)
   *     A list of conditions where the key is the field the condition applies
   *     to and its value consists of the following:
   *     - value (string|array|\Drupal\Core\Database\Query\SelectInterface|null)
   *       the value to test the field against;
   *     - operator (string|null): the operator to use.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A query builder for SELECT statements.
   */
  public function getQuery(array $options = []): SelectInterface;

  /**
   * Returns a list of entries from the log table.
   *
   * @param array $options
   *   (optional) Options for retrieving the entries:
   *   - header (string)
   *     The column to sort the entries by.
   *   - limit (int)
   *     The maximum number of entries to retrieve.
   *   - conditions (array)
   *     A list of conditions where the key is the field the condition applies
   *     to and its value consists of the following:
   *     - value (string|array|\Drupal\Core\Database\Query\SelectInterface|null)
   *       the value to test the field against;
   *     - operator (string|null): the operator to use.
   *
   * @return \stdClass[]
   *   A list of log entries, each consisting of the following:
   *   - entity_id (int)
   *     The ID of the entity that was involved. Can be empty if the entity
   *     failed to import.
   *   - entity_type_id (string)
   *     The type of the entity that was involved.
   *   - entity_label (string)
   *     An alternative for identifying the entity, because the entity may not
   *     exist yet or it may have been deleted.
   *   - item
   *     Uri of the logged item, if available.
   *   - operation (string)
   *     Type of the operation, for example "created", "updated", or "cleaned".
   *   - message
   *     Text of log message.
   *   - Serialized array of variables that match the message string and that is
   *     passed into the t() function.
   *   - timestamp
   *     Unix timestamp of when the event occurred.
   */
  public function getLogEntries(array $options = []): array;

}
