<?php

namespace Drupal\feeds_test_events\EventSubscriber;

use Drupal\feeds\Event\CleanEvent;
use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\EventBase;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * React on authors being processed.
 */
class FeedsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FeedsEvents::FEEDS_DELETE => ['onDelete'],
      FeedsEvents::INIT_IMPORT => ['onInitImport'],
      FeedsEvents::FETCH => [
        ['preFetch', FeedsEvents::BEFORE],
        ['postFetch', FeedsEvents::AFTER],
      ],
      FeedsEvents::PARSE => [
        ['preParse', FeedsEvents::BEFORE],
        ['postParse', FeedsEvents::AFTER],
      ],
      FeedsEvents::PROCESS => [
        ['preProcess', FeedsEvents::BEFORE],
        ['postProcess', FeedsEvents::AFTER],
      ],
      FeedsEvents::PROCESS_ENTITY_PREVALIDATE => ['prevalidate'],
      FeedsEvents::PROCESS_ENTITY_PRESAVE => ['preSave'],
      FeedsEvents::PROCESS_ENTITY_POSTSAVE => ['postSave'],
      FeedsEvents::CLEAN => ['onClean'],
      FeedsEvents::INIT_CLEAR => ['onInitClear'],
      FeedsEvents::CLEAR => ['onClear'],
      FeedsEvents::INIT_EXPIRE => ['onInitExpire'],
      FeedsEvents::EXPIRE => ['onExpire'],
      FeedsEvents::IMPORT_FINISHED => ['onFinish'],
    ];
  }

  /**
   * Acts on multiple feeds getting deleted.
   */
  public function onDelete(DeleteFeedsEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on an import being initiated.
   */
  public function onInitImport(InitEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event before fetching.
   */
  public function preFetch(FetchEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on fetcher result.
   */
  public function postFetch(FetchEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event before parsing.
   */
  public function preParse(ParseEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on parser result.
   */
  public function postParse(ParseEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event before processing.
   */
  public function preProcess(ProcessEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on process result.
   */
  public function postProcess(ProcessEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on an entity before validation.
   */
  public function prevalidate(EntityEvent $event) {
    $this->saveState($event, __METHOD__);

    $feed_type_id = $event->getFeed()->getType()->id();
    switch ($feed_type_id) {
      case 'no_title':
        // A title is required, set a title on the entity to prevent validation
        // errors.
        $event->getEntity()->title = 'foo';
        break;
    }
  }

  /**
   * Acts on presaving an entity.
   */
  public function preSave(EntityEvent $event) {
    $this->saveState($event, __METHOD__);

    $feed_type_id = $event->getFeed()->getType()->id();
    switch ($feed_type_id) {
      case 'import_skip':
        // We do not save the node called 'Lorem ipsum'.
        if ($event->getEntity()->getTitle() == 'Lorem ipsum') {
          throw new EmptyFeedException();
        }
        break;
    }
  }

  /**
   * Acts on postsaving an entity.
   */
  public function postSave(EntityEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on the cleaning stage.
   */
  public function onClean(CleanEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event before deleting items begins.
   */
  public function onInitClear(InitEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event where deleting items has began.
   */
  public function onClear(ClearEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event before expiring items begins.
   */
  public function onInitExpire(InitEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on event where expiring items has began.
   */
  public function onExpire(ExpireEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Acts on the completion of an import.
   */
  public function onFinish(ImportFinishedEvent $event) {
    $this->saveState($event, __METHOD__);
  }

  /**
   * Records which methods were called.
   *
   * @param \Drupal\feeds\Event\EventBase $event
   *   The event being dispatched.
   * @param string $method
   *   The method that was called.
   */
  protected function saveState(EventBase $event, string $method) {
    if ($event instanceof InitEvent) {
      $method .= '(' . $event->getStage() . ')';
    }

    // Save to a global variable.
    $GLOBALS['feeds_test_events'][] = ($method . ' called');

    // And save to a state variable, useful when testing with multiple cron
    // runs.
    $feed_test_events = \Drupal::state()->get('feeds_test_events', []);
    $feed_test_events[] = $method;
    \Drupal::state()->set('feeds_test_events', $feed_test_events);
  }

}
