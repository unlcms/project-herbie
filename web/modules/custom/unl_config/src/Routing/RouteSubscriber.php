<?php

namespace Drupal\unl_config\Routing;

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
    // Form: system_site_information_settings.
    // Route: system.site_information_settings.
    // Path: admin/config/system/site-information.
    if ($route = $collection->get('system.site_information_settings')) {
      // Add custom permission for route.
      $permission = $route->getRequirement('_permission') . '+unl administer site configuration';
      $route->setRequirement('_permission', $permission);
    }

    // Form: admin_toolbar_tools.flush.
    // Route: admin_toolbar_tools.flush.
    // Path: /admin/flush.
    if ($route = $collection->get('admin_toolbar_tools.flush')) {
      // Add custom permission for route.
      $permission = $route->getRequirement('_permission') . '+unl administer site configuration';
      $route->setRequirement('_permission', $permission);
    }
  }

}
