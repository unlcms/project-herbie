<?php

namespace Drupal\feeds\Lock;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\PersistentDatabaseLockBackend;
use Psr\Log\LoggerInterface;

/**
 * Lock backend for Feeds imports.
 *
 * Ensures that feeds don't get unlocked when an import appears
 * to be still running.
 */
class FeedsLockBackend extends PersistentDatabaseLockBackend {

  /**
   * The feed storage.
   *
   * @var \Drupal\feeds\FeedStorageInterface
   */
  protected $feedStorage;

  /**
   * The time in seconds on how long a lock may usually last.
   *
   * @var int
   */
  protected $timeout;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new FeedsLockBackend.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    parent::__construct($database);
    $this->feedStorage = $entity_type_manager->getStorage('feeds_feed');
    $this->timeout = $config_factory->get('feeds.settings')->get('lock_timeout');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function lockMayBeAvailable($name) {
    $name = $this->normalizeName($name);
    if (strpos($name, 'feeds_feed:') !== 0) {
      return parent::lockMayBeAvailable($name);
    }

    try {
      $lock = $this->database->query('SELECT [expire], [value] FROM {semaphore} WHERE [name] = :name', [':name' => $name])->fetchAssoc();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If the table does not exist yet then the lock may be available.
      $lock = FALSE;
    }
    if (!$lock) {
      return TRUE;
    }
    $expire = (float) $lock['expire'];
    $now = microtime(TRUE);
    if ($now > $expire) {
      // The lock is supposed to expire. Check if a feed import is still
      // running.
      $feed_id = str_replace('feeds_feed:', '', $name);
      $feed = $this->feedStorage->load($feed_id);
      if (!$feed) {
        return $this->releaseIfExpired($name, $expire);
      }

      // Check if queue tasks exist for the feed. If so, don't release the lock
      // but extend it instead.
      if ($feed->hasQueueTasks()) {
        $this->extendLock($name, $this->timeout);
        return FALSE;
      }

      // Check if there was recent progress on the feeds import. If so, don't
      // release the lock but extend it instead.
      if ($feed->hasRecentProgress()) {
        $this->extendLock($name, $this->timeout);
        return FALSE;
      }

      // Release the lock in case it has expired.
      return $this->releaseIfExpired($name, $expire);
    }
    return FALSE;
  }

  /**
   * Extends the time of a lock.
   *
   * @param string $name
   *   Lock name. Limit of name's length is 255 characters.
   * @param float $timeout
   *   (optional) Lock lifetime in seconds. Defaults to 30.0.
   *
   * @return bool
   *   True if the lock was extended. False otherwise.
   *
   * @throws \Exception
   *   In case the semaphore table could not be created.
   */
  public function extendLock($name, $timeout = 30.0) {
    if (!$this->ensureTableExists()) {
      throw new \Exception('The semaphore table could not be created.');
    }

    // Imports running for a long time could potentially indicate issues, though
    // it can also happen that the import is very large and just takes long to
    // complete. So only log a notice of this and not a warning.
    $this->logger->notice('Lock @name got extended.', [
      '@name' => $name,
    ]);

    $this->locks[$name] = TRUE;
    return $this->acquire($name, $timeout);
  }

  /**
   * Releases a lock in case it has been expired.
   *
   * @param string $name
   *   The lock name to look for.
   * @param float $expire
   *   The time when the lock was supposed to have been expired.
   *
   * @return bool
   *   True in case the lock was released. False otherwise.
   */
  protected function releaseIfExpired($name, $expire): bool {
    try {
      $lock = $this->database->query('SELECT [expire], [value] FROM {semaphore} WHERE [name] = :name', [':name' => $name])->fetchAssoc();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If the table does not exist yet then the lock may be available.
      $lock = FALSE;
    }
    if (!$lock) {
      return TRUE;
    }

    // We check two conditions to prevent a race condition where another request
    // acquired the lock and set a new expire time. We add a small number to
    // $expire to avoid errors with float to string conversion.
    return (bool) $this->database->delete('semaphore')
      ->condition('name', $name)
      ->condition('value', $lock['value'])
      ->condition('expire', 0.0001 + $expire, '<=')
      ->execute();
  }

}
