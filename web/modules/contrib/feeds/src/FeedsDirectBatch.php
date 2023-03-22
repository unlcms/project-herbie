<?php

namespace Drupal\feeds;

/**
 * A batch task to be executed directly.
 */
class FeedsDirectBatch extends FeedsBatchBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    foreach ($this->operations as $operation) {
      $this->executable->processItem($this->feed, $operation['stage'], $operation['params']);
    }
  }

}
