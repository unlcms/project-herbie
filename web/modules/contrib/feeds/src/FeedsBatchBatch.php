<?php

namespace Drupal\feeds;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A batch task for the batch API.
 */
class FeedsBatchBatch extends FeedsBatchBase {

  use StringTranslationTrait;

  /**
   * Adds a new batch.
   *
   * @param array $batch_definition
   *   An associative array defining the batch.
   */
  protected function batchSet(array $batch_definition) {
    return batch_set($batch_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $batch = [
      'title' => '',
      'operations' => [],
    ];

    foreach ($this->operations as $operation) {
      $batch['operations'][] = [
        [$this->executable, 'processItem'],
        [$this->feed, $operation['stage'], $operation['params']],
      ];
    }

    switch ($this->stage) {
      case FeedsExecutableInterface::FETCH:
        $batch['title'] = $this->t('Fetching: %title', ['%title' => $this->feed->label()]);
        $batch['error_message'] = $this->t('An error occurred while fetching %title.', ['%title' => $this->feed->label()]);
        break;

      case FeedsExecutableInterface::PARSE:
        $batch['title'] = $this->t('Parsing: %title', ['%title' => $this->feed->label()]);
        $batch['error_message'] = $this->t('An error occurred while parsing %title.', ['%title' => $this->feed->label()]);
        break;

      case FeedsExecutableInterface::PROCESS:
        $batch['title'] = $this->t('Processing: %title', ['%title' => $this->feed->label()]);
        $batch['error_message'] = $this->t('An error occurred while processing %title.', ['%title' => $this->feed->label()]);
        break;

      case FeedsExecutableInterface::CLEAN:
        $batch['title'] = $this->t('Cleaning: %title', ['%title' => $this->feed->label()]);
        $batch['error_message'] = $this->t('An error occurred while cleaning %title.', ['%title' => $this->feed->label()]);
        break;
    }

    $batch += [
      'init_message' => $batch['title'],
      'progress_message' => $batch['title'],
    ];

    $this->batchSet($batch);

    return $this;
  }

}
