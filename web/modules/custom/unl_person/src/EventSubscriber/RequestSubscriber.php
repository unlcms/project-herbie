<?php

namespace Drupal\unl_person\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event Subscriber to listen to the kernel.request event.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Subscribe to the request event to trigger on every page load.
    $events[KernelEvents::REQUEST][] = 'onRequest';
    return $events;
  }

  /**
   * Event handler to check for the specific role and remove permissions.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event object.
   */
  public function onRequest(Event $event) {

    $role_name = 'authenticated';
    $permissions_to_check = [
      'use editorial transition publish',
      'edit any person content',
      'use text format simple',
      'use text format standard',
    ];

    // Load the role.
    $role = Role::load($role_name);

    if ($role) {
      foreach ($permissions_to_check as $permission) {
        if ($role->hasPermission($permission)) {
          // Revoke permissions if they were previously set to allow access.
          user_role_revoke_permissions($role->id(), [$permission]);
        }
      }
    }

    $block_id = 'unl_five_herbie_local_tasks';
    $block = \Drupal\block\Entity\Block::load($block_id);
    $visibility = $block->getVisibility();

    // Display the edit/view tabs for authenticated users.
    if (isset($visibility['user_role']['roles']['authenticated'])) {
      unset($visibility['user_role']['roles']['authenticated']);
      $block->setVisibilityConfig('user_role', $visibility['user_role']);
      $block->save();
    }

    $request = \Drupal::service('request_stack')->getCurrentRequest();
    $route_name = $request->attributes->get('_route');

    // Check if the route is a "node" page (view or edit).
    if (strpos($route_name, 'entity.node.') !== FALSE) {
      // Get the node from the request.
      $node = $request->attributes->get('node');
      if ($node) {
        if ($node->getType() === 'person') {
          if ($node->hasField('n_person_unldirectoryreference') && !$node->get('n_person_unldirectoryreference')->isEmpty()) {
            $person_unldirectoryreference_data = $node->get('n_person_unldirectoryreference')->getValue()[0]['target_id'];
            $person_referenced_account = user_load_by_name($person_unldirectoryreference_data);
            // If the n_person_unldirectoryreference field has a value and it matches the current user, allow access.
            $user = \Drupal::currentUser();

            if ($person_referenced_account && $person_referenced_account->id() === $user->id()) {
              $block = \Drupal\block\Entity\Block::load($block_id);
              $visibility = $block->getVisibility();
              $user = \Drupal::currentUser();
              $current_user_roles = $user->getRoles();
              // Display the edit/view tabs for authenticated users.
              if (!isset($visibility['user_role']['roles']['authenticated'])) {
                if ($current_user_roles === ['authenticated']) {
                  $visibility['user_role']['roles']['authenticated'] = 'authenticated';
                  $block->setVisibilityConfig('user_role', $visibility['user_role']);
                  $block->save();
                }
              }
              user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['use editorial transition publish']);
              user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['edit any person content']);
              user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['use text format simple']);
              user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['use text format standard']);
            }
          }
        }
      }
    }
  }
}
