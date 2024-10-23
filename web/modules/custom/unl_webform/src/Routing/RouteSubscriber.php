<?php

namespace Drupal\unl_webform\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase
{

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection)
  {
    // Make Help pages inaccessible.
    if ($route = $collection->get('webform.help')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
    if ($route = $collection->get('webform.help.video')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
    if ($route = $collection->get('entity.webform_options.collection')) {
      // Override the Webform module's permission with a custom permission to restrict access to Options configurations only.
      $route->setRequirement('_permission', 'access unl webform options');
    }
    if ($route = $collection->get('entity.webform_options.source_form')) {
      $requirements = $route->getRequirements();
      // Remove the Webform module's _custom_access to allow the addition of a YAML source for predefined lists, restricted to users with 'access unl webform options' permission.
      if (isset($requirements['_custom_access'])) {
        unset($requirements['_custom_access']);
        $route->setRequirements($requirements);
      }
      // Override the permission with a custom permission to restrict access to Options configurations only.
      $route->setRequirement('_permission', 'access unl webform options');
    }
  }
}
