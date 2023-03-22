<?php

namespace Drupal\feeds\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fired when one or more feeds is being deleted.
 */
class DeleteFeedsEvent extends Event {

  /**
   * The feeds being deleted.
   *
   * @var \Drupal\feeds\FeedInterface[]
   */
  protected $feeds;

  /**
   * Constructs a new DeleteFeedsEvent object.
   *
   * @param \Drupal\feeds\FeedInterface[] $feeds
   *   A list of feed entities.
   */
  public function __construct(array $feeds) {
    $this->feeds = $feeds;
  }

  /**
   * Returns the feeds being deleted.
   *
   * @return \Drupal\feeds\FeedInterface[]
   *   A list of feeds being deleted.
   */
  public function getFeeds() {
    return $this->feeds;
  }

}
