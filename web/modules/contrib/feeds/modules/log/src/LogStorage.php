<?php

namespace Drupal\feeds_log;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\feeds\FeedInterface;

/**
 * Controller class for Feeds Import Log entities.
 */
class LogStorage extends SqlContentEntityStorage implements LogStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function generate(FeedInterface $feed) {
    $log = $this->create([
      'start' => time(),
      'feed' => $feed->id(),
    ]);
    return $log;
  }

}
