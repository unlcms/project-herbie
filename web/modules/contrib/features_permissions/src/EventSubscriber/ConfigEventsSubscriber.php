<?php

namespace Drupal\features_permissions\EventSubscriber;

use Drupal\config_update\ConfigRevertEvent;
use Drupal\config_update\ConfigRevertInterface;
use Drupal\features_permissions\PermissionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A config events subscriber.
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * The permission manager service.
   *
   * @var \Drupal\features_permissions\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * Class constructor.
   */
  public function __construct(PermissionManagerInterface $permission_manager) {
    $this->permissionManager = $permission_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Only sync permissions to roles on config import/revert. Take no action
   * on deletion. When a permission is removed from a Feature, management
   * ceases at that point. Configuration should not be altered or removed.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigRevertInterface::IMPORT => 'featureConfigImport',
      ConfigRevertInterface::REVERT => 'featureConfigRevert',
    ];
  }

  /**
   * Sync permissions to roles on features import.
   */
  public function featureConfigImport(ConfigRevertEvent $event) {
    if ($event->getType() == 'user_permission') {
      $name = $event->getName();
      $perm_key = $this->permissionManager->getPermissionKeyFromMachineName($name);
      $this->permissionManager->syncPermissionToRoles($perm_key);
    }
  }

  /**
   * Sync permissions to roles on features revert.
   */
  public function featureConfigRevert(ConfigRevertEvent $event) {
    if ($event->getType() == 'user_permission') {
      $name = $event->getName();
      $perm_name = $this->permissionManager->getPermissionKeyFromMachineName($name);
      $this->permissionManager->syncPermissionToRoles($perm_name);
    }
  }

}
