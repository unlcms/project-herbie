<?php

namespace Drupal\feeds_log\EventSubscriber;

use Drupal\feeds_log\Event\FeedsLogEvents;
use Drupal\feeds_log\Event\StampedeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to import log overload creation event.
 */
class StampedeSubscriber implements EventSubscriberInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new StampedeSubscriber object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsLogEvents::STAMPEDE_DETECTION][] = 'onStampede';
    return $events;
  }

  /**
   * Reacts on a lot of imports happening in a short amount of time.
   */
  public function onStampede(StampedeEvent $event) {
    // Disable logging for this feed.
    $feed = $event->getFeed();
    $feed->feeds_log->value = FALSE;
    $feed->save();

    // Log an error.
    $this->logger->error('Logging for Feed @id disabled due to a lot import logs getting created in a short amount of time.');
  }

}
