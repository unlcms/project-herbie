<?php

namespace Drupal\feeds;

/**
 * Defines an interface for importing feeds.
 */
interface FeedsExecutableInterface {

  /**
   * Parameter passed when starting a new import.
   *
   * @var string
   */
  const BEGIN = 'begin';

  /**
   * Parameter passed when fetching.
   *
   * @var string
   */
  const FETCH = 'fetch';

  /**
   * Parameter passed when parsing.
   *
   * @var string
   */
  const PARSE = 'parse';

  /**
   * Parameter passed when processing.
   *
   * @var string
   */
  const PROCESS = 'process';

  /**
   * Parameter passed when cleaning.
   *
   * @var string
   */
  const CLEAN = 'clean';

  /**
   * Parameter passed when finishing.
   *
   * @var string
   */
  const FINISH = 'finish';

  /**
   * Processes a stage of an import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to batch.
   * @param string $stage
   *   The stage which the import is at.
   * @param array $params
   *   Parameters relevant to the current stage.
   */
  public function processItem(FeedInterface $feed, $stage, array $params = []);

}
