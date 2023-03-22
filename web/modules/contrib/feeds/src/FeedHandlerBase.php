<?php

namespace Drupal\feeds;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds\Event\EventDispatcherTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base class for entity handlers.
 */
abstract class FeedHandlerBase implements EntityHandlerInterface {

  use DependencySerializationTrait;
  use EventDispatcherTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new FeedHandlerBase object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, Connection $database) {
    $this->setEventDispatcher($event_dispatcher);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('database')
    );
  }

  /**
   * Adds a new batch.
   *
   * @param array $batch_definition
   *   An associative array defining the batch.
   */
  protected function batchSet(array $batch_definition) {
    return batch_set($batch_definition);
  }

}
