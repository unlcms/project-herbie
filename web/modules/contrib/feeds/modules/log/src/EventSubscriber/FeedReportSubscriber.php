<?php

namespace Drupal\feeds_log\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\feeds\Event\EventBase;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ReportEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds_log\Event\FeedsLogEvents;
use Drupal\feeds_log\Event\StampedeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to various feeds events to keep track of an import.
 */
class FeedReportSubscriber implements EventSubscriberInterface {

  /**
   * The storage handler for feeds_import_log entities.
   *
   * @var \Drupal\feeds_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The feeds log configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Provides an object for obtaining system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new FeedReportSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The object for obtaining system time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher, LogMessageParserInterface $parser, TimeInterface $time) {
    $this->logStorage = $entity_type_manager->getStorage('feeds_import_log');
    $this->config = $config_factory->get('feeds_log.settings');
    $this->dispatcher = $event_dispatcher;
    $this->parser = $parser;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::INIT_IMPORT][] = 'onInitImport';
    $events[FeedsEvents::FETCH][] = ['afterFetch', FeedsEvents::AFTER];
    $events[FeedsEvents::REPORT][] = 'logReport';
    $events[FeedsEvents::IMPORT_FINISHED][] = 'onFinish';
    return $events;
  }

  /**
   * Checks if logging is enabled.
   *
   * @param \Drupal\feeds\Event\EventBase $event
   *   The dispatched Feeds event.
   *
   * @return bool
   *   True if logging is enabled. False otherwise.
   */
  protected function isLoggingEnabled(EventBase $event): bool {
    return $event->getFeed()->getType()->getThirdPartySetting('feeds_log', 'status') !== FALSE
      && (bool) $event->getFeed()->feeds_log->value;
  }

  /**
   * Reacts on the import being started.
   *
   * @param \Drupal\feeds\Event\InitEvent $event
   *   The import init event.
   */
  public function onInitImport(InitEvent $event) {
    if (!$this->isLoggingEnabled($event)) {
      // Do not log anything.
      return;
    }

    // Prevent a sprawl of import logs getting created. Check how many import
    // logs have been created lately.
    $stampede = $this->config->get('stampede');
    $time_threshold = $this->time->getRequestTime() - $stampede['age'];
    $amount = $this->logStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('start', $time_threshold, '>')
      ->condition('feed', $event->getFeed()->id())
      ->count()
      ->execute();
    if ($amount >= $stampede['max_amount']) {
      // Abort logging and dispatch an event.
      $this->dispatcher->dispatch(new StampedeEvent($event->getFeed()), FeedsLogEvents::STAMPEDE_DETECTION);
      return;
    }

    $import_log = $this->getImportLog($event->getFeed());
    if (!$import_log) {
      // Create a new import log entry.
      $import_state = $event->getFeed()->getState('import');
      $import_log = $this->logStorage->generate($event->getFeed());
      $import_log->save();

      // Create a record for this import.
      $import_state->import_id = $import_log->id();
    }
  }

  /**
   * Reacts on the fetch event after the source data is fetched.
   *
   * @param \Drupal\feeds\Event\FetchEvent $event
   *   The fetch event.
   */
  public function afterFetch(FetchEvent $event) {
    if (!$this->isLoggingEnabled($event)) {
      // Do not log anything.
      return;
    }
    if ($event->getFeed()->getType()->getThirdPartySetting('feeds_log', 'source') === FALSE) {
      // Do not log the source.
      return;
    }

    $import_log = $this->getImportLog($event->getFeed());
    if ($import_log) {
      $import_log->logSource($event->getFetcherResult());
      $import_log->save();
    }
  }

  /**
   * Logs a reported event.
   *
   * @param \Drupal\feeds\Event\ReportEvent $event
   *   The report event.
   */
  public function logReport(ReportEvent $event) {
    if (!$this->isLoggingEnabled($event)) {
      // Do not log anything.
      return;
    }

    $enabled_operations = $event->getFeed()->getType()->getThirdPartySetting('feeds_log', 'operations');
    if (!is_null($enabled_operations) && empty($enabled_operations[$event->getOperation()])) {
      // Do not log this event.
      return;
    }

    $import_log = $this->getImportLog($event->getFeed());
    if (!$import_log) {
      // If no import log was created, then abort.
      return;
    }
    $context = $event->getContext();
    $message = $event->getMessage();

    // Convert PSR3-style messages to \Drupal\Component\Render\FormattableMarkup
    // style, so they can be translated too in runtime.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    // Initial values for the log entry to record.
    $record = [
      'operation' => $event->getOperation(),
      'message' => $message,
      'timestamp' => $this->time->getRequestTime(),
      'variables' => serialize($message_placeholders),
    ];

    // Record the ID and type of the entity that was processed, if there is any.
    if (isset($context['entity']) && $context['entity'] instanceof EntityInterface) {
      $record['entity_type_id'] = $context['entity']->getEntityType()->id();
      $record['entity_id'] = $context['entity']->id();
    }

    // Record an alternative way of identifying the entity, useful if the entity
    // failed to import.
    if (isset($context['entity_label'])) {
      [$entity_label, $type] = array_values($context['entity_label']);
      $record['entity_label'] = strtr('@type: @entity_label', [
        '@type' => $type,
        '@entity_label' => $entity_label,
      ]);
    }

    // Record current item ids.
    if (isset($context['item']) && $context['item'] instanceof ItemInterface) {
      $record['item_id'] = implode(';', $this->getItemIds($event->getFeed(), $context['item']));
    }

    // Create the log entry.
    $index = $import_log->addLogEntry($record);

    // Log the parsed item if it exists and if it is configured to be logged.
    if (isset($context['item']) && $context['item'] instanceof ItemInterface) {
      // Check if items should get logged for the current operation.
      $enabled_items = $event->getFeed()->getType()->getThirdPartySetting('feeds_log', 'items');
      if (is_null($enabled_items) || !empty($enabled_items[$event->getOperation()])) {
        // Log item to file.
        $record['item'] = $import_log->logItem($context['item'], $index);
        $import_log->updateLogEntry($record);
      }
    }
  }

  /**
   * Reacts on the import being finished.
   *
   * @param \Drupal\feeds\Event\ImportFinishedEvent $event
   *   The finish event.
   */
  public function onFinish(ImportFinishedEvent $event) {
    // We don't need to explicitly check if logging is disabled here. If it got
    // disabled while an import was running, we still set the finish time for
    // it.
    $import_log = $this->getImportLog($event->getFeed());
    if ($import_log) {
      $import_log->end->value = time();
      $import_log->save();
    }
  }

  /**
   * Returns the import log object, if found.
   *
   * @param \Drupal\Feeds\FeedInterface $feed
   *   The feed entity.
   *
   * @return \Drupal\feeds_log\ImportLogInterface|null
   *   An import log object or null if none is found.
   */
  protected function getImportLog(FeedInterface $feed) {
    $import_state = $feed->getState('import');
    if (empty($import_state->import_id)) {
      return;
    }
    return $this->logStorage->load($import_state->import_id);
  }

  /**
   * Tries to get the properties from the item that were marked as unique.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to find an id for.
   *
   * @return string[]
   *   A list of item ID's.
   */
  protected function getItemIds(FeedInterface $feed, ItemInterface $item) {
    $return = [];
    foreach ($feed->getType()->getMappings() as $delta => $mapping) {
      if (empty($mapping['unique'])) {
        continue;
      }

      foreach ($mapping['unique'] as $key => $true) {
        $return[] = $key . ':' . $item->get($mapping['map'][$key]);
      }
    }

    return $return;
  }

}
