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
  }

}
