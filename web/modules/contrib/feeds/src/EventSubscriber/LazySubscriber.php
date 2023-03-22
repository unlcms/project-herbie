<?php

namespace Drupal\feeds\EventSubscriber;

use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Event\CleanEvent;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\CleanableInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\StateInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener that registers Feeds plugins as event listeners.
 */
class LazySubscriber implements EventSubscriberInterface {

  /**
   * Wether the import listeners have been added.
   *
   * @var array
   */
  protected $importInited = [];

  /**
   * Wether the clear listeners have been added.
   *
   * @var bool
   */
  protected $clearInited = FALSE;

  /**
   * Wether the expire listeners have been added.
   *
   * @var bool
   */
  protected $expireInited = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::INIT_IMPORT][] = 'onInitImport';
    $events[FeedsEvents::INIT_CLEAR][] = 'onInitClear';
    $events[FeedsEvents::INIT_EXPIRE][] = 'onInitExpire';
    return $events;
  }

  /**
   * Adds import plugins as event listeners.
   */
  public function onInitImport(InitEvent $event, $event_name, EventDispatcherInterface $dispatcher) {
    $stage = $event->getStage();

    if (isset($this->importInited[$stage])) {
      return;
    }
    $this->importInited[$stage] = TRUE;

    switch ($stage) {
      case 'fetch':
        $dispatcher->addListener(FeedsEvents::FETCH, function (FetchEvent $event) {
          $feed = $event->getFeed();
          $result = $feed->getType()->getFetcher()->fetch($feed, $feed->getState(StateInterface::FETCH));
          $event->setFetcherResult($result);
        });
        break;

      case 'parse':
        $dispatcher->addListener(FeedsEvents::PARSE, function (ParseEvent $event) {
          $feed = $event->getFeed();

          $result = $feed
            ->getType()
            ->getParser()
            ->parse($feed, $event->getFetcherResult(), $feed->getState(StateInterface::PARSE));

          // Add data from source plugins to the parser result.
          $source_plugins = $this->getMappedSourcePlugins($feed->getType());
          if (!empty($source_plugins)) {
            /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
            foreach ($result as $item) {
              /** @var \Drupal\feeds\Plugin\Type\Source\SourceInterface $source_plugin */
              foreach ($source_plugins as $source => $source_plugin) {
                $item->set($source, $source_plugin->getSourceElement($feed, $item));
              }
            }
          }

          // Finally set the parser result on the event.
          $event->setParserResult($result);
        });
        break;

      case 'process':
        $dispatcher->addListener(FeedsEvents::PROCESS, function (ProcessEvent $event) {
          $feed = $event->getFeed();
          $feed
            ->getType()
            ->getProcessor()
            ->process($feed, $event->getItem(), $feed->getState(StateInterface::PROCESS));
        });
        break;

      case 'clean':
        foreach ($event->getFeed()->getType()->getPlugins() as $plugin) {
          if (!$plugin instanceof CleanableInterface) {
            continue;
          }

          $dispatcher->addListener(FeedsEvents::CLEAN, function (CleanEvent $event) use ($plugin) {
            try {
              $feed = $event->getFeed();
              $plugin->clean($feed, $event->getEntity(), $feed->getState(StateInterface::CLEAN));
            }
            catch (Exception $e) {
              watchdog_exception('feeds', $e);
            }
          });
        }
        break;

    }
  }

  /**
   * Adds clear plugins as event listeners.
   */
  public function onInitClear(InitEvent $event, $event_name, EventDispatcherInterface $dispatcher) {
    if ($this->clearInited === TRUE) {
      return;
    }
    $this->clearInited = TRUE;

    foreach ($event->getFeed()->getType()->getPlugins() as $plugin) {
      if (!$plugin instanceof ClearableInterface) {
        continue;
      }

      $dispatcher->addListener(FeedsEvents::CLEAR, function (ClearEvent $event) use ($plugin) {
        $feed = $event->getFeed();
        $plugin->clear($feed, $feed->getState(StateInterface::CLEAR));
      });
    }
  }

  /**
   * Adds expire plugins as event listeners.
   */
  public function onInitExpire(InitEvent $event, $event_name, EventDispatcherInterface $dispatcher) {
    if ($this->expireInited === TRUE) {
      return;
    }
    $this->expireInited = TRUE;

    $dispatcher->addListener(FeedsEvents::EXPIRE, function (ExpireEvent $event) {
      $feed = $event->getFeed();
      $state = $feed->getState(StateInterface::EXPIRE);

      $feed->getType()
        ->getProcessor()
        ->expireItem($feed, $event->getItemId(), $state);

      $feed->saveStates();
    });
  }

  /**
   * Returns all source plugins used in mapping.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to get mapped source plugins from.
   *
   * @return \Drupal\feeds\Plugin\Type\Source\SourceInterface[]
   *   A list of instantiated source plugins.
   */
  protected function getMappedSourcePlugins(FeedTypeInterface $feed_type) {
    $source_plugins = [];

    foreach ($feed_type->getMappedSources() as $source) {
      if ($plugin = $feed_type->getSourcePlugin($source)) {
        $source_plugins[$source] = $plugin;
      }
    }

    return $source_plugins;
  }

}
