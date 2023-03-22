<?php

namespace Drupal\feeds\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A base class for manipulating parser results.
 */
abstract class AfterParseBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    return $events;
  }

  /**
   * Acts on parser result.
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParse(ParseEvent $event) {
    if (!$this->applies($event)) {
      return;
    }

    /** @var \Drupal\feeds\Result\ParserResultInterface $result */
    $result = $event->getParserResult();

    for ($i = 0; $i < $result->count(); $i++) {
      if (!$result->offsetExists($i)) {
        break;
      }

      /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
      $item = $result->offsetGet($i);

      try {
        $this->alterItem($item, $event);
      }
      catch (SkipItemException $e) {
        $result->offsetUnset($i);
        $i--;
      }
    }
  }

  /**
   * Returns if parsing should apply.
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   *
   * @return bool
   *   True, if altering should continue.
   *   False otherwise.
   */
  public function applies(ParseEvent $event) {
    return TRUE;
  }

  /**
   * Alters a single item.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   *
   * @throws \Drupal\feeds\Exception\SkipItemException
   *   In case the item should not be imported.
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {}

}
