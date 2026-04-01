<?php

namespace Drupal\unl_contenthub\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity_share_client.admin_content_page')) {
      $route->setRequirement('_custom_access', '\Drupal\unl_contenthub\Access\ContentHubPullAccess::access');
    }
    if ($route = $collection->get('entity_share_client.admin_content_pull_form')) {
      $route->setRequirement('_custom_access', '\Drupal\unl_contenthub\Access\ContentHubPullAccess::access');
    }
  }
}
