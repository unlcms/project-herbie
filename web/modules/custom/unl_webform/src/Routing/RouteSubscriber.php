<?php

namespace Drupal\unl_webform\Routing;

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
    // Make Help pages inaccessible.
    if ($route = $collection->get('webform.help')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
    if ($route = $collection->get('webform.help.video')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
  }

}
