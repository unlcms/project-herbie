<?php

namespace Drupal\feeds;

use Drupal\feeds\Result\FetcherResultInterface;

/**
 * Import feeds using the batch API.
 */
class FeedsBatchExecutable extends FeedsExecutable {

  /**
   * {@inheritdoc}
   */
  protected function createBatch(FeedInterface $feed, $stage) {
    return new FeedsBatchBatch($this, $feed, $stage);
  }

  /**
   * {@inheritdoc}
   */
  protected function finish(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $result = parent::finish($feed, $fetcher_result);
    if ($result) {
      // Start a batch for expiring items.
      $feed->startBatchExpire();
    }
  }

}
