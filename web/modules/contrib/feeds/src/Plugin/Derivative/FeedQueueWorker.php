<?php

namespace Drupal\feeds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides separate queue works for feed types.
 *
 * @see \Drupal\feeds\Plugin\QueueWorker\FeedRefresh
 */
class FeedQueueWorker extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs an FeedQueueWorker object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity manager.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity_type.manager')->getStorage('feeds_feed_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];
    foreach ($this->storage->loadMultiple() as $feed_type) {
      $derivatives[$feed_type->id()] = [
        'title' => $this->t('Feed refresh: @feed_type_label', [
          '@feed_type_label' => $feed_type->label(),
        ]),
      ] + $base_plugin_definition;
    }

    return $derivatives;
  }

}
