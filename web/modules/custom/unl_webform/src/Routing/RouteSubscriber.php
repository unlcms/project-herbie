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
    // Make Access tab on webform entity inaccessible.
    // Webform should make this a permission.
    if ($route = $collection->get('entity.webform.settings_access')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
  }
  
}
