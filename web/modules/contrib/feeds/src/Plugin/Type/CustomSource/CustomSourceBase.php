<?php

namespace Drupal\feeds\Plugin\Type\CustomSource;

use Drupal\Component\Utility\NestedArray;
use Drupal\feeds\Plugin\Type\ConfigurablePluginTrait;
use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * Base class for custom source plugins.
 */
abstract class CustomSourceBase extends PluginBase implements CustomSourceInterface {

  use ConfigurablePluginTrait;

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Merge with default configuration.
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function additionalColumns(array $custom_source) {
    // By default, there are no additional columns to display for the custom
    // source.
    return [];
  }

}
