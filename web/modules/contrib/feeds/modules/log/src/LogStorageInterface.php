<?php

namespace Drupal\feeds_log;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\feeds\FeedInterface;

/**
 * Interface for saving feed logs.
 */
interface LogStorageInterface extends ContentEntityStorageInterface {

  /**
   * Generates a log.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed whose import is tracked.
   *
   * @return \Drupal\feeds_log\LogInterface
   *   The generated log, unsaved.
   */
  public function generate(FeedInterface $feed);

}
