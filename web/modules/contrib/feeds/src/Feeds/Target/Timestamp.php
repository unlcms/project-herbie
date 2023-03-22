<?php

namespace Drupal\feeds\Feeds\Target;

use Drupal\Component\Datetime\DateTimePlus;

/**
 * Defines a timestamp field mapper.
 *
 * @FeedsTarget(
 *   id = "timestamp",
 *   field_types = {
 *     "created",
 *     "timestamp"
 *   }
 * )
 */
class Timestamp extends DateTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $date = $this->convertToDate($values['value']);

    if ($date instanceof DateTimePlus) {
      $values['value'] = $date->getTimestamp();
    }
    else {
      $values['value'] = '';
    }
  }

}
