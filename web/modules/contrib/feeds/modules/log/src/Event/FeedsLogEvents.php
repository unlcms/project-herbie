<?php

namespace Drupal\feeds_log\Event;

/**
 * Defines events for the Feeds Log module.
 */
final class FeedsLogEvents {

  /**
   * Fired when a lot of import logs are created in a short amount of time.
   *
   * By default this event gets fired when 100 import log entities for the same
   * feed get created within 30 minutes.
   */
  const STAMPEDE_DETECTION = 'feeds_log.stampede_detection';

}
