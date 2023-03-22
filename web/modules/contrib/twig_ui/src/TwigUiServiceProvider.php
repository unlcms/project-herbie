<?php

namespace Drupal\twig_ui;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\twig_ui\Theme\RegistryDecorator;

/**
 * Replaces core's theme registry service.
 */
class TwigUiServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace core's theme registry.
    $definition = $container->getDefinition('theme.registry');
    $definition->setClass(RegistryDecorator::class);
  }

}
