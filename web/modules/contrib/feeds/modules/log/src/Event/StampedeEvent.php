<?php

namespace Drupal\feeds_log\Event;

use Drupal\feeds\Event\EventBase;

/**
 * Fired when a lot of import logs are created in a short amount of time.
 *
 * By default this event gets fired when 100 import log entities for the same
 * feed get created within 30 minutes.
 */
class StampedeEvent extends EventBase {
}
