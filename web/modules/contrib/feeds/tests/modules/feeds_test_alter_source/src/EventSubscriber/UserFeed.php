<?php

namespace Drupal\feeds_test_alter_source\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters the parsed result for the feeds importing users.
 */
class UserFeed implements EventSubscriberInterface {

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
    if ($event->getFeed()->getType()->id() != 'user_import') {
      // Not interested in this feed. Abort.
      return;
    }

    /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
    foreach ($event->getParserResult() as $item) {
      // Convert roles value to multiple values.
      foreach (['role_ids', 'role_labels'] as $source_name) {
        $data = $item->get($source_name);
        if (!empty($data)) {
          $item->set($source_name, explode('|', $data));
        }
      }
    }
  }

}
