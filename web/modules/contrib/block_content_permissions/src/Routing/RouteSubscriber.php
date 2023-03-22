<?php

namespace Drupal\block_content_permissions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The AccessControlHandler class name.
   *
   * @var string
   */
  private $accessControlHandlerClassName = 'Drupal\block_content_permissions\AccessControlHandler';

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Move block content listing page; replace permission requirement.
    if ($route = $collection->get('entity.block_content.collection')) {
      $route->setPath('admin/content/block-content');
      $route->setDefault(
        '_title', 'Custom blocks'
      );
      $route->addRequirements([
        '_permission' => 'access block content overview',
      ]);
    }

    // Change access and controller callback for the block content add page.
    if ($route = $collection->get('block_content.add_page')) {
      $route->addRequirements([
        '_custom_access' => $this->accessControlHandlerClassName . '::blockContentAddPageAccess',
      ]);
      $route->setDefault(
        '_controller',
        'Drupal\block_content_permissions\Controller\BlockContentPermissionsAddPageController::add'
      );
      // Remove required "administer blocks" permission.
      $this->removePermissionRequirement($route);
    }

    // Change access callback for the block content add forms.
    if ($route = $collection->get('block_content.add_form')) {
      $route->addRequirements([
        '_custom_access' => $this->accessControlHandlerClassName . '::blockContentAddFormAccess',
      ]);
      // Remove required "administer blocks" permission.
      $this->removePermissionRequirement($route);
    }

    // Move block type listing page; replace permission requirement.
    if ($route = $collection->get('entity.block_content_type.collection')) {
      $route->setPath('admin/structure/block-types');
      $route->setDefault(
        '_title', 'Block types'
      );
      $route->addRequirements([
        '_permission' => 'administer block content types',
      ]);
    }

    // Replacement permission requirement for add block type route.
    if ($route = $collection->get('block_content.type_add')) {
      $route->addRequirements([
        '_permission' => 'administer block content types',
      ]);
    }

    // Move block type type edit page.
    if ($route = $collection->get('entity.block_content_type.edit_form')) {
      $route->setPath('admin/structure/block-types/manage/{block_content_type}');
    }

    // Move block type type delete page.
    if ($route = $collection->get('entity.block_content_type.delete_form')) {
      $route->setPath('admin/structure/block-types/manage/{block_content_type}/delete');
    }
  }

  /**
   * Remove required "administer blocks" permission from route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The Route object.
   */
  private function removePermissionRequirement(Route $route) {
    if ($route->hasRequirement('_permission')) {
      $requirements = $route->getRequirements();
      unset($requirements['_permission']);
      $route->setRequirements($requirements);
    }
  }

}
