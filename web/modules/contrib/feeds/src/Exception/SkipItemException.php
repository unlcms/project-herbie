<?php

namespace Drupal\feeds\Exception;

/**
 * Thrown if the import of a single item should be skipped.
 *
 * This exception should only be thrown by event subscribers that extend
 * \Drupal\feeds\EventSubscriber\AfterParseBase.
 */
class SkipItemException extends FeedsRuntimeException {}
