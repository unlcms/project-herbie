<?php

namespace Drupal\unl_person\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Event Subscriber to listen to the kernel.request event.
 */
class RequestSubscriber implements EventSubscriberInterface {
  protected $currentUser;
  protected $aliasManager;
  protected $messenger;
  private $reload_page = false;
  private $temporary_editor = "temporary_editor";

  public function __construct(AccountProxyInterface $current_user, AliasManagerInterface $aliasManager, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->aliasManager = $aliasManager;
    $this->messenger = $messenger;
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Subscribe to the request event to trigger on every page load after hook_node_access
    // and routing processing, both of which run before priority 32.
    // The priority is currently set to the default value.
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    $events[KernelEvents::RESPONSE][] = ['onResponse'];

    return $events;
  }

  /**
   * Event handler to dynamically assign user rights on the Person content type based on specific conditions.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event object.
   */
  public function onRequest(Event $event) {
    $user = User::load($this->currentUser->id());
    $current_user_roles = $user->getRoles();
    $request = $event->getRequest();
    $session = $request->getSession();
    $route_name = $request->attributes->get('_route');
    $entity_form = $request->attributes->get('_entity_form');
    $node = $request->attributes->get('node');
    $roles_to_check = ['editor', 'administrator', 'super_administrator', 'coder', 'site_admin'];
    $image_upload_route = false;

    // Check if logged in user is referenced on the Person page.
    $curent_user_is_referenced = function () use ($node, $roles_to_check, $current_user_roles, $user, $entity_form, $route_name, $request) {
      if ($entity_form == 'node.edit' || strpos($route_name, 'entity.node.') !== FALSE) {
        if ($node && $node instanceof Node && $node->getType() === 'person' && empty(array_intersect($roles_to_check, $current_user_roles))) {
          if ($node->hasField('n_person_unldirectoryreference') && !$node->get('n_person_unldirectoryreference')->isEmpty()) {
            $person_unldirectoryreference_data = $node->get('n_person_unldirectoryreference')->getValue()[0]['target_id'];
            $person_referenced_account = user_load_by_name($person_unldirectoryreference_data);
            if ($person_referenced_account && $person_referenced_account->id() === $user->id()) {
              return true;
            }
          }
        }
      }
    };
    $curent_user_is_referenced = $curent_user_is_referenced();

    // User adding an image to an image field on a node.
    if ($route_name === "image.style_public") {
      $image_upload_route = true;
    }

    // Display the edit/view tabs for all roles as defined in the configuration file (except Authenticated).
    // This restores the default block configuration if there were any changes made to it.
    $block_id = \Drupal::config('system.theme')->get('default') . '_local_tasks';
    $block = \Drupal\block\Entity\Block::load($block_id);
    if($block) {
      $visibility = $block->getVisibility();
    }

    if (isset($visibility) && isset($visibility['user_role']['roles']['authenticated'])) {
      unset($visibility['user_role']['roles']['authenticated']);
      $block->setVisibilityConfig('user_role', $visibility['user_role']);
      $block->save();
    }
    // If user is authenticated and has the temporary role, remove the temporary role.
    if ($user->isAuthenticated()) {
      if ($current_user_roles) {
        $role_to_check = [$this->temporary_editor];
        $role_exists = !array_diff($role_to_check, array_values($current_user_roles));
        if ($role_exists && $curent_user_is_referenced !== true) {
          // Remove the temporary role from the user, but keep any existing roles if they're on an image upload route, as they could potentially be uploading an image for a person page.
          if ($user && $image_upload_route === false) {
            $user->removeRole($this->temporary_editor);
            $user->save();
          }
        }
      }
      // Check if the route is a "node" page (view or edit)
      if ($entity_form == 'node.edit' || strpos($route_name, 'entity.node.') !== FALSE) {
        // Get the node from the request.
        if ($node && $node instanceof Node) {
          $role_to_check = [$this->temporary_editor];
          $role_exists = !array_diff($role_to_check, array_values($current_user_roles));
          if ($curent_user_is_referenced === true) {
            // Check if node is a person content type and if the user does not have any of the editor/admin roles.
            $roles_to_check = ['authenticated', $this->temporary_editor];
            $roles_exist = !array_diff($roles_to_check, array_values($current_user_roles));
            if (!isset($visibility['user_role']['roles']['authenticated'])) {
              // Assign the temporary role to the user, provided the role is not already assigned.
              if ($user && empty($role_exists)) {
                $user->addRole($this->temporary_editor);
                $user->save();
                $this->reload_page = true;
              }
              // Display the edit/view tabs for authenticated users.
              if ($current_user_roles === ['authenticated'] || $roles_exist) {
                $visibility['user_role']['roles']['authenticated'] = 'authenticated';
                $block->setVisibilityConfig('user_role', $visibility['user_role']);
                $block->save();
              }

              // Add a warning message to the user when they are on the edit page and navigate to another UNL page.
              if ($entity_form == 'node.edit' && strpos($route_name, 'entity.node.') !== FALSE) {
                $this->messenger->addWarning('Leaving this page and navigating to another logged-in UNL webpage will require a page refresh upon return to regain edit and save access. Please make sure to save your changes before leaving this edit page, or keep a backup of any modifications in another location.');
              }
            }
          }
        }
      }
      if ($this->reload_page === false) {
        $this->removeSessionKey($session, 'page_reloaded');
      }
    }
  }

  /**
   * Handles the kernel.response event.
   * Reload page to sync the user roles assigned to the user so the edit tab shows up.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *
   */
  public function onResponse(ResponseEvent $event) {
    $request = $event->getRequest();
    if (isset($request->attributes) && $request->attributes->has('node')) {
      $node = $request->attributes->get('node');
      if ($node instanceof \Drupal\node\NodeInterface && $node->access('view') && $node->getType() === 'person') {
        $session = $request->getSession();
        // Check if the page requires reloading and ensure it hasn't already been reloaded to prevent an infinite redirect loop.
        if ($this->reload_page && !$session->has('page_reloaded')) {
          $node_url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
          // Redirect user to their person page.
          $event->setResponse(new RedirectResponse($node_url));
          $session->set('page_reloaded', true);
        }
      }
    }
  }

  public static function create(ContainerInterface $container) {
    return new static(

      $container->get('current_user'),
      $container->get('path.alias_manager'),
      $container->get('messenger')
    );
  }

  private function removeSessionKey($session, $key) {
    if ($session->has($key)) {
      $session->remove($key);
    }
  }
}
