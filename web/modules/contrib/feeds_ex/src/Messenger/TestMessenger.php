<?php

namespace Drupal\feeds_ex\Messenger;

use Drupal\Core\Messenger\Messenger;

/**
 * Stores messages without calling drupal_set_message().
 */
class TestMessenger extends Messenger {

  /**
   * The messages that have been set.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * Constructs a new TestMessenger.
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE) {
    $this->messages[] = [
      'message' => $message,
      'type' => $type,
      'repeat' => $repeat,
    ];
  }

  /**
   * Returns the messages.
   *
   * This is used to inspect messages that have been set.
   *
   * @return array
   *   A list of message arrays.
   */
  public function getMessages() {
    return $this->messages;
  }

}
