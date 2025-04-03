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
    $block_id = 'unl_five_herbie_local_tasks';
    $block = \Drupal\block\Entity\Block::load($block_id);
    $visibility = $block->getVisibility();
    $request = $event->getRequest();
    $session = $request->getSession();
    $route_name = $request->attributes->get('_route');
    $entity_form = $request->attributes->get('_entity_form');
    $node = $request->attributes->get('node');
    $roles_to_check = ['editor', 'administrator', 'super_administrator', 'coder', 'site_admin'];

    // Display the edit/view tabs for all roles as defined in the configuration file (except Authenticated).
    // This restores the default block configuration if there were any changes made to it.
    if (isset($visibility['user_role']['roles']['authenticated'])) {
      unset($visibility['user_role']['roles']['authenticated']);
      $block->setVisibilityConfig('user_role', $visibility['user_role']);
      $block->save();
    }
    // If user is authenticated and has the temporary role, remove the temporary role.
    if ($user->isAuthenticated()) {
      if ($current_user_roles) {
        $role_to_check = [$this->temporary_editor];
        $role_exists = !array_diff($role_to_check, array_values($current_user_roles));
        if ($role_exists) {
          if ($user) {
            $user->removeRole($this->temporary_editor);
            $user->save();
          }
        }
      }
      // Check if the route is a "node" page (view or edit)
      if ($entity_form == 'node.edit' || strpos($route_name, 'entity.node.') !== FALSE) {
        // Get the node from the request.
        if ($node && $node instanceof Node) {
          // Check if node is a person content type and if the user does not have any of the editor/admin roles.
          if ($node->getType() === 'person' && empty(array_intersect($roles_to_check, $current_user_roles))) {
            if ($node->hasField('n_person_unldirectoryreference') && !$node->get('n_person_unldirectoryreference')->isEmpty()) {
              $person_unldirectoryreference_data = $node->get('n_person_unldirectoryreference')->getValue()[0]['target_id'];
              $person_referenced_account = user_load_by_name($person_unldirectoryreference_data);
              // If the n_person_unldirectoryreference field has a value and it matches the current user, allow local task block/menu access.
              if ($person_referenced_account && $person_referenced_account->id() === $user->id()) {
                $roles_to_check = ['authenticated', $this->temporary_editor];
                $roles_exist = !array_diff($roles_to_check, array_values($current_user_roles));
                if (!isset($visibility['user_role']['roles']['authenticated'])) {
                  if ($user) {
                    // Add the temporary role to the user.
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
                    $this->messenger->addWarning('Leaving this page and navigating to another logged-in UNL webpage will require a page refresh upon return to regain edit and save access. Please ensure you save your changes before leaving this edit page or keep a backup of any modifications elsewhere.');
                  }
                }
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
    $session = $request->getSession();
    // Check if the page requires reloading and ensure it hasn't already been reloaded to prevent an infinite redirect loop.
    if ($this->reload_page && !$session->has('page_reloaded')) {
      $request = $event->getRequest();
      $url = $request->getUri();
      // Redirect user to the same page to reload the page.
      $event->setResponse(new RedirectResponse($url));
      $session->set('page_reloaded', true);
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
