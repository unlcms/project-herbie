<?php

namespace Drupal\feeds_test_alter_source\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters the parsed result for the feed type 'csv'.
 */
class CsvFeed implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FeedsEvents::PARSE => [
        ['afterParse', FeedsEvents::AFTER],
      ],
    ];
  }

  /**
   * Acts on parser result.
   */
  public function afterParse(ParseEvent $event) {
    if ($event->getFeed()->getType()->id() != 'csv') {
      // Not interested in this feed. Abort.
      return;
    }

    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      // Set title to lowercase.
      $item->set('service_description', strtolower($item->get('service_description')));

      // Keep only the first word of "à la carte".
      $carte = $item->get('a_la_carte');
      $carte = strtok($carte, ' ');
      $carte = preg_replace('/[^a-zA-Z\-]/', '', $carte);
      $item->set('a_la_carte', $carte);
    }
  }

}
