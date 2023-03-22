<?php

namespace Drupal\feeds\Annotation;

/**
 * Defines a Plugin annotation object for Feeds custom source plugins.
 *
 * Custom sources are user defined source fields. They are mostly used by
 * parsers that don't provide predefined source fields.
 *
 * Plugin Namespace: Feeds\CustomSource.
 *
 * @see \Drupal\feeds\Plugin\Type\FeedsPluginManager
 * @see \Drupal\feeds\Plugin\Type\CustomSource\CustomSourceInterface
 * @see \Drupal\feeds\Plugin\Type\PluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsCustomSource extends FeedsBase {

}
