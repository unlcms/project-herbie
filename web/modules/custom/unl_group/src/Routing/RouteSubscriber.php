<?php

namespace Drupal\unl_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // If no Groups exist and the user isn't a power user then hide the Groups
    // toolbar menu item by denying access to the route.
    if ($route = $collection->get('entity.group.collection')) {
      $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple();
      if (!$groups && empty(array_intersect(['super_administrator', 'administrator', 'coder', 'temporary_development_contributor'], \Drupal::currentUser()->getRoles()))) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
