<?php

namespace Drupal\feeds;

use Drupal\Core\Queue\QueueFactory;

/**
 * A batch task for the queue API.
 */
class FeedsQueueBatch extends FeedsBatchBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new FeedsQueueBatch object.
   *
   * @param \Drupal\feeds\FeedsExecutableInterface $executable
   *   The Feeds executable.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to run a batch for.
   * @param string $stage
   *   The stage of the batch to run.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(FeedsExecutableInterface $executable, FeedInterface $feed, $stage, QueueFactory $queue_factory) {
    parent::__construct($executable, $feed, $stage);
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Queue all operations now.
    foreach ($this->operations as $operation) {
      $this->queueFactory->get('feeds_feed_refresh:' . $this->feed->bundle())
        ->createItem([
          $this->feed->id(),
          $operation['stage'],
          $operation['params'],
        ]);
    }
  }

}
