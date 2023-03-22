<?php

namespace Drupal\feeds\Plugin\Type\Target;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Interface for configurable target plugins.
 */
interface ConfigurableTargetInterface extends ConfigurableInterface, DependentPluginInterface {

  /**
   * Returns the summary for a target.
   *
   * Returning the summary as array is encouraged. The allowance of returning a
   * string only exists for backwards compatibility.
   *
   * @return string|string[]
   *   The configuration summary.
   */
  public function getSummary();

}
