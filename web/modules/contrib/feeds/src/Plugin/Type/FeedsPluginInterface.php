<?php

namespace Drupal\feeds\Plugin\Type;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface that all Feeds plugins must implement.
 */
interface FeedsPluginInterface extends PluginInspectionInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * Returns the type of plugin.
   *
   * @return string
   *   The type of plugin. Usually, one of 'fetcher', 'parser', or 'processor'.
   *
   * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager::processDefinition()
   */
  public function pluginType();

  /**
   * Returns default feed configuration.
   *
   * @return array
   *   The default feed configuration.
   */
  public function defaultFeedConfiguration();

}
