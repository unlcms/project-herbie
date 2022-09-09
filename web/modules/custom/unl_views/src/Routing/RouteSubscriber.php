<?php

namespace Drupal\unl_views\Routing;

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
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        case 'entity.view.enable':
        case 'entity.view.disable':
        case 'entity.view.duplicate_form':
        case 'entity.view.delete_form':
        case 'entity.view.edit_form':
        case 'entity.view.edit_display_form':
        case 'entity.view.preview_form':
        case 'entity.view.break_lock_form':
          $route->setRequirements(['_custom_access' => '\Drupal\unl_views\Access\ProductionAccess::viewAccess']);
          break;
      }
    }
  }
}
