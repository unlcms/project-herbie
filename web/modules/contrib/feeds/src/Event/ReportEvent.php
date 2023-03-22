<?php

namespace Drupal\feeds\Event;

use Drupal\feeds\FeedInterface;

/**
 * Fired when a modification on an item gets reported.
 *
 * Examples of modifications are:
 * - created;
 * - updated;
 * - deleted.
 *
 * @see \Drupal\feeds\StateType
 */
class ReportEvent extends EventBase {

  /**
   * The type of modification.
   *
   * @var string
   */
  protected $operation;

  /**
   * The reported message.
   *
   * @var string
   */
  protected $message;

  /**
   * The context data.
   *
   * @var array
   */
  protected $context;

  /**
   * Constructs a new ReportEvent object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param string $operation
   *   What happened to the imported item.
   * @param string $message
   *   (optional) The reported message.
   * @param array $context
   *   (optional) The context data for the report event.
   */
  public function __construct(FeedInterface $feed, $operation, $message = '', array $context = []) {
    parent::__construct($feed);
    $this->operation = $operation;
    $this->message = $message;
    $this->context = $context;
  }

  /**
   * Returns what happened to an item.
   *
   * @return string
   *   The type of modification.
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * Returns the reported message.
   *
   * @return string
   *   The reported message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Returns the context data.
   *
   * @return array
   *   The context data. This can contain:
   *   - \Drupal\feeds\Feeds\Item\ItemInterface item
   *       In case when an entity was created or updated.
   *   - \Drupal\Core\Entity\EntityInterface entity
   *       The entity that was modified.
   *   - array|null entity_label
   *       An array consisting of three elements with the following keys:
   *       - label (string): the label of the entity.
   *       - type (string): the type of identification.
   *       - id (string): the entity ID, if there is one.
   *       Can also be null.
   */
  public function getContext() {
    return $this->context;
  }

}
