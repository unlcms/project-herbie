<?php

namespace Drupal\feeds\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Wrapper methods for the event dispatcher interface.
 *
 * If the class is capable of injecting services from the container, it should
 * inject the 'event_dispatcher' service and assign it to
 * $this->eventDispatcher.
 *
 * @see \Symfony\Component\EventDispatcher\EventDispatcherInterface
 */
trait EventDispatcherTrait {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $_eventDispatcher;

  /**
   * Dispatches an event.
   *
   * @param string $event_name
   *   The name of the event.
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   The event to dispatch.
   *
   * @return \Symfony\Contracts\EventDispatcher\Event
   *   The invoked event.
   */
  protected function dispatchEvent($event_name, Event $event = NULL) {
    return $this->getEventDispatcher()->dispatch($event, $event_name);
  }

  /**
   * Returns the event dispatcher service.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher service.
   */
  protected function getEventDispatcher() {
    if (!isset($this->_eventDispatcher)) {
      $this->_eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->_eventDispatcher;
  }

  /**
   * Sets the event dispatcher service to use.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function setEventDispatcher(EventDispatcherInterface $event_dispatcher) {
    $this->_eventDispatcher = $event_dispatcher;
  }

}
