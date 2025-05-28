<?php

namespace Drupal\unl_news\Plugin\QueueWorker;

/**
 * Processes Node Tasks.
 *
 * @QueueWorker(
 *   id = "ianrnews_queue_processor",
 *   title = @Translation("Task Worker: IANR News Articles"),
 *   cron = {"time" = 20}
 * )
 */
class IANRNewsQueueProcessor extends NebraskaTodayQueueProcessor {

  /**
   * The name of the website being fetched from.
   *
   * @var string
   */
  const PUBLICATION_NAME = 'IANR News';

}
