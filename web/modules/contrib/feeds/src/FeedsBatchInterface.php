<?php

namespace Drupal\feeds;

/**
 * Interface for Feeds batched tasks.
 */
interface FeedsBatchInterface {

  /**
   * Adds an operation.
   *
   * @param string $stage
   *   The stage of the operation to add.
   * @param array $params
   *   (optional) A list of parameters for the operation.
   *
   * @return $this
   *   An instance of this class.
   */
  public function addOperation($stage, array $params = []);

  /**
   * Runs the batch.
   *
   * @return $this
   *   An instance of this class.
   */
  public function run();

}
