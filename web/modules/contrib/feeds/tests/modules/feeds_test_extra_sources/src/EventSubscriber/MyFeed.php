<?php

namespace Drupal\feeds_test_extra_sources\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters the parsed result for the feed type 'my_feed'.
 */
class MyFeed implements EventSubscriberInterface {

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
    if ($event->getFeed()->getType()->id() != 'my_feed') {
      // Not interested in this feed. Abort.
      return;
    }

    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      $title = $item->get('title');
      $slogan = $item->get('site:slogan');

      // Set title to lowercase.
      $item->set('title', strtolower($title));

      // Get first word from title.
      $word = strtok($title, ' ');
      // Strip all chars except letters and dashes.
      $word = preg_replace('/[^a-zA-Z\-]/', '', $word);

      // And alter slogan.
      $item->set('site:slogan', str_replace('It', $word, $slogan));
    }
  }

}
