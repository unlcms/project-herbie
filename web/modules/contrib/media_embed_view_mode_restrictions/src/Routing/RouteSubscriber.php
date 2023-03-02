<?php

namespace Drupal\media_embed_view_mode_restrictions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * This can be removed when https://www.drupal.org/node/3109289 is fixed.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override Media Dialog route.
    if ($route = $collection->get('editor.media_dialog')) {
      $route->setDefault('_form', '\Drupal\media_embed_view_mode_restrictions\Form\EditorMediaDialogDecorator');
    }
  }

}
