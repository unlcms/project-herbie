<?php

namespace Drupal\feeds;

/**
 * Base class for batched feeds tasks.
 */
abstract class FeedsBatchBase implements FeedsBatchInterface {

  /**
   * The Feeds executable.
   *
   * @var \Drupal\feeds\FeedsExecutableInterface
   */
  protected $executable;

  /**
   * The feed to run a batch for.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The stage of the batch to run.
   *
   * @var string
   */
  protected $stage;

  /**
   * A list of operations to run.
   *
   * @var array
   */
  protected $operations = [];

  /**
   * Constructs a new FeedsBatchBase object.
   *
   * @param \Drupal\feeds\FeedsExecutableInterface $executable
   *   The Feeds executable.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to run a batch for.
   * @param string $stage
   *   The stage of the batch to run.
   */
  public function __construct(FeedsExecutableInterface $executable, FeedInterface $feed, $stage) {
    $this->executable = $executable;
    $this->feed = $feed;
    $this->stage = $stage;
  }

  /**
   * {@inheritdoc}
   */
  public function addOperation($stage, array $params = []) {
    $this->operations[] = ['stage' => $stage, 'params' => $params];
    return $this;
  }

}
