<?php

namespace Drupal\block_content_permissions;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the block content permissions.
 */
class AccessControlHandler implements ContainerInjectionInterface {

  /**
   * The block content types.
   *
   * @var array
   */
  protected $blockContentTypes;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Constructs the block content access control handler instance.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   Route match interface.
   */
  public function __construct(RouteMatchInterface $currentRouteMatch) {
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

  /**
   * Returns the block content types.
   *
   * @return array
   *   The block content types.
   */
  protected function blockContentTypes() {
    if (!$this->blockContentTypes) {
      $this->blockContentTypes = \Drupal::entityQuery('block_content_type')
        ->accessCheck(TRUE)
        ->execute();
    }
    return $this->blockContentTypes;
  }

  /**
   * Returns the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = $this->container()->get('current_user');
    }
    return $this->currentUser;
  }

  /**
   * Access check for the block content add page.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result.
   */
  public function blockContentAddPageAccess() {
    $orPermissions = [];
    foreach ($this->blockContentTypes() as $bundle_type) {
      $orPermissions[] = "create $bundle_type block content";
    }
    $account = $this->currentUser();
    return AccessResult::allowedIfHasPermissions($account, $orPermissions, 'OR');
  }

  /**
   * Access check for the block content add forms.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result.
   */
  public function blockContentAddFormAccess(RouteMatchInterface $route_match) {
    if ($block_content_type = $route_match->getParameter('block_content_type')) {
      $bundle_type = $block_content_type->get('id');
      $account = $this->currentUser();
      return AccessResult::allowedIfHasPermission($account, "create $bundle_type block content");
    }
    return AccessResult::neutral();
  }

}
