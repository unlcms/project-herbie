<?php

namespace Drupal\feeds\Plugin\Type\Source;

use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * Base class for source plugins.
 */
abstract class SourceBase extends PluginBase implements SourceInterface {

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $defaults = $this->defaultConfiguration();
    $this->configuration = $configuration + $defaults;
  }

}
