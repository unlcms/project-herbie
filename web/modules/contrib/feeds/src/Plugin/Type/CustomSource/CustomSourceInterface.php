<?php

namespace Drupal\feeds\Plugin\Type\CustomSource;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;

/**
 * Interface for sources that can be defined in the UI.
 */
interface CustomSourceInterface extends FeedsPluginInterface, PluginFormInterface {

  /**
   * Defines additional rows for display on the custom sources list page.
   *
   * @param array $custom_source
   *   The custom source data.
   */
  public function additionalColumns(array $custom_source);

}
