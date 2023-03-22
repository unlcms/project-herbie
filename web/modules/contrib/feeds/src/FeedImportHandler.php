<?php

namespace Drupal\feeds;

use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\Result\RawFetcherResult;

/**
 * Runs the actual import on a feed.
 */
class FeedImportHandler extends FeedHandlerBase {

  /**
   * Imports the whole feed at once.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import for.
   *
   * @throws \Exception
   *   In case of an error.
   */
  public function import(FeedInterface $feed) {
    $this->getExecutable(FeedsExecutable::class)
      ->processItem($feed, FeedsExecutable::BEGIN);
  }

  /**
   * Starts importing a feed via the batch API.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if a feed is locked.
   */
  public function startBatchImport(FeedInterface $feed) {
    $this->getExecutable(FeedsBatchExecutable::class)
      ->processItem($feed, FeedsBatchExecutable::BEGIN);
  }

  /**
   * Starts importing a feed via cron.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to queue.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if a feed is locked.
   */
  public function startCronImport(FeedInterface $feed) {
    if ($feed->isLocked()) {
      $args = ['@id' => $feed->bundle(), '@fid' => $feed->id()];
      throw new LockException($this->t('The feed @id / @fid is locked.', $args));
    }

    $this->getExecutable(FeedsQueueExecutable::class)
      ->processItem($feed, FeedsQueueExecutable::BEGIN);

    // Add timestamp to avoid queueing item more than once.
    $feed->setQueuedTime($this->getRequestTime());
    $feed->save();
  }

  /**
   * Handles a push import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed receiving the push.
   * @param string $payload
   *   The feed contents.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   (optional) The file system service.
   */
  public function pushImport(FeedInterface $feed, $payload, FileSystemInterface $file_system = NULL) {
    $feed->lock();
    $fetcher_result = new RawFetcherResult($payload, $file_system);

    $this->getExecutable(FeedsQueueExecutable::class)
      ->processItem($feed, FeedsQueueExecutable::PARSE, [
        'fetcher_result' => $fetcher_result,
      ]);
  }

  /**
   * Checks if there are still tasks on the feeds queue.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to look for in the queue.
   *
   * @return bool
   *   True if there are still tasks on the queue. False otherwise.
   */
  public function hasQueueTasks(FeedInterface $feed): bool {
    // First check if the queue table exists.
    if (!$this->database->schema()->tableExists('queue')) {
      // No queue table exists, so no tasks can exist on it.
      return FALSE;
    }

    $result = $this->database->select('queue')
      ->fields('queue', [])
      ->condition('data', 'a:3:{i:0;i:' . $feed->id() . ';%', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();

    return $result > 0;
  }

  /**
   * Removes all queue tasks for the given feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed for which to remove queue tasks.
   */
  public function clearQueueTasks(FeedInterface $feed): void {
    if (!$this->database->schema()->tableExists('queue')) {
      return;
    }
    $this->database->delete('queue')
      ->condition('data', 'a:3:{i:0;i:' . $feed->id() . ';%', 'LIKE')
      ->execute();
  }

  /**
   * Checks if there was recent import activity.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to check.
   * @param int $seconds
   *   (optional) How far to look back. Defaults to 3600 seconds (one hour).
   *
   * @return bool
   *   True if there was recently progress reported. False otherwise.
   */
  public function hasRecentProgress(FeedInterface $feed, int $seconds = 3600): bool {
    // Get the last activity for this feed.
    $last_activity = \Drupal::keyValue('feeds_feed.' . $feed->id())->get('last_activity');

    if (!$last_activity) {
      // No last activity known.
      return FALSE;
    }

    // Check if the last activity was within the last specified number of
    // seconds.
    return $last_activity > ($this->getRequestTime() - $seconds);
  }

  /**
   * Returns the timestamp for the current request.
   *
   * @return int
   *   A Unix timestamp.
   */
  protected function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * Returns the executable.
   *
   * @param string $class
   *   The class to load.
   *
   * @return \Drupal\feeds\FeedsExecutableInterface
   *   A feeds executable.
   */
  protected function getExecutable($class) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition($class);
  }

}
