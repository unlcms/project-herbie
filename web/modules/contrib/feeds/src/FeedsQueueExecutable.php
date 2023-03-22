<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Import feeds using the queue API.
 */
class FeedsQueueExecutable extends FeedsExecutable {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new FeedsQueueExecutable object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, AccountSwitcherInterface $account_switcher, MessengerInterface $messenger, QueueFactory $queue_factory) {
    parent::__construct($entity_type_manager, $event_dispatcher, $account_switcher, $messenger);
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('account_switcher'),
      $container->get('messenger'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function createBatch(FeedInterface $feed, $stage) {
    return new FeedsQueueBatch($this, $feed, $stage, $this->queueFactory);
  }

  /**
   * {@inheritdoc}
   */
  protected function handleException(FeedInterface $feed, $stage, array $params, \Exception $exception) {
    if ($exception instanceof EmptyFeedException) {
      $feed->finishImport();
      return;
    }

    // On an exception, the queue item remains on the queue so we need to keep
    // the feed locked.
    throw $exception;
  }

}
