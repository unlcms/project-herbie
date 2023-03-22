<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a feeds_feed entity.
 */
interface FeedInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Represents an active feed.
   *
   * @var int
   */
  const ACTIVE = 1;

  /**
   * Represents an inactive feed.
   *
   * @var int
   */
  const INACTIVE = 0;

  /**
   * Returns the source of the feed.
   *
   * @return string
   *   The source of a feed.
   */
  public function getSource();

  /**
   * Sets the feed source.
   *
   * @param string $source
   *   The feed source.
   */
  public function setSource($source);

  /**
   * Returns the feed type object that this feed is expected to be used with.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   The feed type object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case the feed type could not be loaded.
   */
  public function getType();

  /**
   * Returns the feed creation timestamp.
   *
   * @return int
   *   Creation timestamp of the feed.
   */
  public function getCreatedTime();

  /**
   * Sets the feed creation timestamp.
   *
   * @param int $timestamp
   *   The feed creation timestamp.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the feed imported timestamp.
   *
   * @return int
   *   Creation timestamp of the feed.
   */
  public function getImportedTime();

  /**
   * Returns the next time the feed will be imported.
   *
   * @return int
   *   The next time the feed will be imported as a UNIX timestamp.
   */
  public function getNextImportTime();

  /**
   * Returns the time when this feed was queued for refresh, 0 if not queued.
   *
   * @return int
   *   The timestamp of the last refresh.
   */
  public function getQueuedTime();

  /**
   * Sets the time when this feed was queued for refresh, 0 if not queued.
   *
   * @param int $queued
   *   The timestamp of the last refresh.
   */
  public function setQueuedTime($queued);

  /**
   * Imports the whole feed at once.
   *
   * This does not batch. It assumes that the input is small enough to not need
   * it.
   *
   * @throws \Exception
   *   Re-throws any exception that bubbles up.
   */
  public function import();

  /**
   * Starts importing a feed via the batch API.
   *
   * @throws \Exception
   *   Thrown if an un-recoverable error has occurred.
   */
  public function startBatchImport();

  /**
   * Starts importing a feed via cron.
   *
   * @throws \Exception
   *   Thrown if an un-recoverable error has occurred.
   */
  public function startCronImport();

  /**
   * Imports a raw string.
   *
   * This does not batch. It assumes that the input is small enough to not need
   * it.
   *
   * @param string $raw
   *   (optional) A raw string to import.
   *
   * @throws \Exception
   *   Re-throws any exception that bubbles up.
   *
   * @todo We need to create a job for this that will run immediately so that
   *   services don't have to wait for us to process. Can we spawn a background
   *   process?
   */
  public function pushImport($raw);

  /**
   * Start deleting all imported items of a feed via the batch API.
   *
   * @throws \Exception
   *   If processing in background is enabled, the first batch chunk of the
   *   clear task will be executed on the current page request.
   */
  public function startBatchClear();

  /**
   * Removes all expired items from a feed via batch api.
   *
   * @throws \Exception
   *   Re-throws any exception that bubbles up.
   */
  public function startBatchExpire();

  /**
   * Checks if there are still tasks on the feeds queue.
   *
   * @return bool
   *   True if there are still tasks on the queue. False otherwise.
   */
  public function hasQueueTasks(): bool;

  /**
   * Removes all queue tasks for the current feed.
   */
  public function clearQueueTasks(): void;

  /**
   * Checks if there was recent import activity.
   *
   * @param int $seconds
   *   (optional) How far to look back. Defaults to 3600 seconds (one hour).
   *
   * @return bool
   *   True if there was recently progress reported. False otherwise.
   */
  public function hasRecentProgress(int $seconds = 3600): bool;

  /**
   * Dispatches an entity event.
   *
   * @param string $event
   *   The event to invoke.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being inserted or updated.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item that is being processed.
   */
  public function dispatchEntityEvent($event, EntityInterface $entity, ItemInterface $item);

  /**
   * Cleans up after an import.
   */
  public function finishImport();

  /**
   * Cleans up after feed items have been deleted.
   */
  public function finishClear();

  /**
   * Reports the progress of the fetching stage.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressFetching();

  /**
   * Reports the progress of the parsing stage.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressParsing();

  /**
   * Reports the progress of the import process.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressImporting();

  /**
   * Reports progress on cleaning.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressCleaning();

  /**
   * Reports progress on clearing.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressClearing();

  /**
   * Reports progress on expiry.
   *
   * @return float
   *   A float between 0 and 1. 1 = StateInterface::BATCH_COMPLETE.
   */
  public function progressExpiring();

  /**
   * Returns a state object for a given stage.
   *
   * Lazily instantiates new states.
   *
   * @param string $stage
   *   One of StateInterface::FETCH, StateInterface::PARSE,
   *   StateInterface::PROCESS or StateInterface::CLEAR.
   *
   * @return \Drupal\feeds\StateInterface
   *   The State object for the given stage.
   */
  public function getState($stage);

  /**
   * Sets a state object for a given stage.
   *
   * @param string $stage
   *   One of StateInterface::FETCH, StateInterface::PARSE,
   *   StateInterface::PROCESS or StateInterface::CLEAR.
   * @param \Drupal\feeds\StateInterface|null $state
   *   A state object or null to unset the state for the given stage.
   */
  public function setState($stage, StateInterface $state = NULL);

  /**
   * Clears all state objects for the feed.
   */
  public function clearStates();

  /**
   * Saves all state objects on the key/value collection of the feed.
   */
  public function saveStates();

  /**
   * Counts items imported by this feed.
   *
   * @return int
   *   The number of items imported by this Feed.
   */
  public function getItemCount();

  /**
   * Returns the configuration for a specific client plugin.
   *
   * @param \Drupal\feeds\Plugin\Type\FeedsPluginInterface $client
   *   A Feeds plugin.
   *
   * @return array
   *   The plugin configuration being managed by this Feed.
   */
  public function getConfigurationFor(FeedsPluginInterface $client);

  /**
   * Sets the configuration for a specific client plugin.
   *
   * @param \Drupal\feeds\Plugin\Type\FeedsPluginInterface $client
   *   A Feeds plugin.
   * @param array $config
   *   The configuration for the plugin.
   *
   * @todo Refactor this. This can cause conflicts if different plugin types
   *   use the same id.
   */
  public function setConfigurationFor(FeedsPluginInterface $client, array $config);

  /**
   * Returns the feed active status.
   *
   * Inactive feeds do not get imported.
   *
   * @return bool
   *   True if the feed is active.
   */
  public function isActive();

  /**
   * Sets the active status of a feed.
   *
   * @param bool $active
   *   True to set this feed to active, false to set it to inactive.
   */
  public function setActive($active);

  /**
   * Locks a feed.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if the lock is unavailable.
   */
  public function lock();

  /**
   * Unlocks a feed.
   */
  public function unlock();

  /**
   * Checks whether a feed is locked.
   *
   * @return bool
   *   Returns true if the feed is locked, and false if not.
   */
  public function isLocked();

}
