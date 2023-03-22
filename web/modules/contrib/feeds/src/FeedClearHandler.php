<?php

namespace Drupal\feeds;

use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\InitEvent;

/**
 * Deletes the items of a feed.
 */
class FeedClearHandler extends FeedHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function startBatchClear(FeedInterface $feed) {
    $feed->lock();
    $feed->clearStates();

    $batch = [
      'title' => $this->t('Deleting items from: %title', ['%title' => $feed->label()]),
      'init_message' => $this->t('Deleting items from: %title', ['%title' => $feed->label()]),
      'operations' => [
        [[$this, 'clear'], [$feed]],
      ],
      'progress_message' => $this->t('Deleting items from: %title', ['%title' => $feed->label()]),
      'error_message' => $this->t('An error occored while clearing %title.', ['%title' => $feed->label()]),
    ];

    $this->batchSet($batch);
  }

  /**
   * Deletes all items from a feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed entity from which to delete all items.
   * @param array $context
   *   Status of the batch.
   */
  public function clear(FeedInterface $feed, array &$context) {
    try {
      $this->dispatchEvent(FeedsEvents::INIT_CLEAR, new InitEvent($feed));
      $this->dispatchEvent(FeedsEvents::CLEAR, new ClearEvent($feed));
    }
    catch (\Exception $exception) {
      // Do nothing yet.
    }

    // Clean up.
    $context['finished'] = $feed->progressClearing();

    if (isset($exception)) {
      $context['finished'] = StateInterface::BATCH_COMPLETE;
    }

    if ($context['finished'] === StateInterface::BATCH_COMPLETE) {
      $feed->finishClear();
      $feed->save();
      $feed->unlock();
    }
    else {
      $feed->saveStates();
    }

    if (isset($exception)) {
      throw $exception;
    }
  }

}
