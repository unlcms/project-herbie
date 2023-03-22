<?php

namespace Drupal\feeds\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;

/**
 * Decorator that prefers non-derived plugins over derived ones.
 *
 * This way plugins that are generated from a deriver can be overridden with a
 * specific one.
 */
class OverridableDerivativeDiscoveryDecorator extends ContainerDerivativeDiscoveryDecorator {

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $plugin_definitions = $this->decorated->getDefinitions();

    $derivative_plugin_definitions = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      if ($this->getDeriver($plugin_id, $plugin_definition)) {
        $derivative_plugin_definitions[$plugin_id] = $plugin_definition;
        unset($plugin_definitions[$plugin_id]);
      }
    }

    return $plugin_definitions + $this->getDerivatives($derivative_plugin_definitions);
  }

}
