<?php

namespace Drupal\feeds_log\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Exposes log types to the views module.
 *
 * @ViewsFilter("feeds_log_operations")
 */
class LogOperations extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = _feeds_log_get_operations();
    }
    return $this->valueOptions;
  }

}
