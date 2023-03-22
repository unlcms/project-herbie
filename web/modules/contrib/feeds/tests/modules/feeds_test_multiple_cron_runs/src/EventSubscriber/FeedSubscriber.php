<?php

namespace Drupal\feeds_test_multiple_cron_runs\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ProcessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to feeds events.
 */
class FeedSubscriber implements EventSubscriberInterface {

  /**
   * The settings of the 'Feeds test multiple cron runs' module.
   *
   * @var Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new FeedSubscriber object.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('feeds_test_multiple_cron_runs.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FeedsEvents::PROCESS => [
        ['afterProcess', FeedsEvents::AFTER],
      ],
    ];
  }

  /**
   * Delays execution after limit is reached.
   */
  public function afterProcess(ProcessEvent $event) {
    static $processed = 0;
    $processed++;

    $limit = $this->config->get('import_queue_time');
    if ($processed == $limit) {
      sleep($limit);
    }
  }

}
