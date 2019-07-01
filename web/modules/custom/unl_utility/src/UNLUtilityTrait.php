<?php

namespace Drupal\unl_utility;

/**
 * Utility methods.
 */
trait UNLUtilityTrait {

  /**
   * Returns an entity object from the current route.
   *
   * @return object|null
   *   Entity object, if one exists; otherwise NULL
   */
  public static function getRouteEntity() {
    $route_match = \Drupal::routeMatch();
    // Entity will be found in the route parameters.
    if (($route = $route_match->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $route_match->getParameter($name);
          if (!empty($entity)) {
            return $entity;
          }
          // Since entity was found, no need to iterate further.
          return NULL;
        }
      }
    }
  }

}
