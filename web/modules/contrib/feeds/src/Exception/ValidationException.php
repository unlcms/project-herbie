<?php

namespace Drupal\feeds\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Thrown if validation of a feed item fails.
 */
class ValidationException extends FeedsRuntimeException {

  /**
   * Returns the formatted message.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   A formatted message.
   */
  public function getFormattedMessage() {
    return new FormattableMarkup($this->getMessage(), []);
  }

}
