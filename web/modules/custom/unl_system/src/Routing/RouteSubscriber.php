<?php

namespace Drupal\unl_system\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    // Change the theme_per_user module's user setting page to use the admin theme.
    if ($route = $collection->get('theme_per_user.theme_select')) {
      $route->setOption('_admin_route', TRUE);
    }

    if ($route = $collection->get('system.site_maintenance_mode')) {
      // Override the Core System module's maintenance mode enabler permission
      // so anyone who is an administrator can use it.
      $requirements = $route->getRequirements();
      unset($requirements['_permission']);
      $route->setRequirements($requirements);
      $route->setRequirement('_role', 'super_administrator+administrator');
    }
  }
}
