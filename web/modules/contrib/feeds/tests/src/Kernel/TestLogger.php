<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logger for testing log messages.
 */
class TestLogger implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * Array of logged messages.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    $this->messages[] = strtr($message, $context);
  }

  /**
   * Returns the logged messages.
   *
   * @return array
   *   An array of all logged messages.
   */
  public function getMessages(): array {
    return $this->messages;
  }

}
