<?php

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsQueueExecutable;

/**
 * A queue worker for importing feeds.
 *
 * @QueueWorker(
 *   id = "feeds_feed_refresh",
 *   title = @Translation("Feed refresh"),
 *   cron = {"time" = 60},
 *   deriver = "Drupal\feeds\Plugin\Derivative\FeedQueueWorker"
 * )
 */
class FeedRefresh extends FeedQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    [$feed, $stage, $params] = $data;

    // In earlier versions of Feeds, a full Feed object was put on the queue.
    // In such case, check if the feed still exists. Or else abort.
    if ($feed instanceof FeedInterface && !$this->feedExists($feed)) {
      return;
    }

    // Load the feed if an ID is given. This is the default.
    if (is_numeric($feed)) {
      $feed = $this->feedLoad($feed);
    }

    // If we have no feed by now, abort the process.
    if (!$feed instanceof FeedInterface) {
      return;
    }

    $this->getExecutable()->processItem($feed, $stage, $params);
  }

  /**
   * Returns Feeds executable.
   *
   * @return \Drupal\feed\FeedsExecutableInterface
   *   A feeds executable.
   */
  protected function getExecutable() {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(FeedsQueueExecutable::class);
  }

  /**
   * Loads a feed entity.
   *
   * @param int $fid
   *   The feed entity ID to load.
   *
   * @return \Drupal\feeds\FeedInterface|null
   *   The feed entity or NULL otherwise.
   */
  protected function feedLoad($fid) {
    return $this->entityTypeManager->getStorage('feeds_feed')->load($fid);
  }

  /**
   * Returns if a feed entity still exists or not.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed entity to check for existence in the database.
   *
   * @return bool
   *   True if the feed still exists, false otherwise.
   */
  protected function feedExists(FeedInterface $feed) {
    // Check if the feed still exists.
    $result = $this->entityTypeManager->getStorage($feed->getEntityTypeId())
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('fid', $feed->id())
      ->execute();
    if (empty($result)) {
      // The feed in question has been deleted.
      return FALSE;
    }
    return TRUE;
  }

}
