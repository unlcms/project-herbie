<?php

namespace Drupal\features_permissions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\features_permissions\Entity\Permission;
use Drupal\user\RoleInterface;

/**
 * The Permission Manager service.
 */
class PermissionManager implements PermissionManagerInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The permission configuration prefix.
   *
   * @var string
   */
  protected $configPrefix;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->configPrefix = $this->entityTypeManager->getDefinition('user_permission')->getConfigPrefix();
  }

  /**
   * {@inheritdoc}
   */
  public function syncRoleToPermissions(RoleInterface $role, string $op): void {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface */
    $storage = $this->entityTypeManager->getStorage('user_permission');
    $role_id = $role->id();
    $permissions = $role->getPermissions();

    // Process added permissions for insert and update operations.
    if ($op == 'insert' || $op == 'update') {
      // Get role permissions prior to update or an empty array for
      // insert operation.
      $permissions_original = ($op == 'update') ? $role->original->getPermissions() : [];

      $added_permissions = array_diff($permissions, $permissions_original);
      foreach ($added_permissions as $added_permission) {
        $id = $this->getPermissionMachineNameFromKey($added_permission);
        // Attempt to load permission config entity.
        /** @var Drupal\Core\Config\Entity\ConfigEntityInterface|NULL */
        $perm_entity = $storage->load($id);

        // If permission config entity doesn't exist, then create it.
        if (is_null($perm_entity)) {
          $perm_entity = Permission::create([
            'id' => $id,
            'label' => $added_permission,
            'roles' => [
              $role_id,
            ],
          ]);
          $perm_entity->save();
        }
        // Otherwise, work with existing permission config entity.
        else {
          if (!$perm_entity->permissionHasRole($role_id)) {
            $perm_entity->addRoleToPermission($role_id);
            $perm_entity->save();
          }
        }
      }
    }

    // Process removed permissions for update and delete operations.
    if ($op == 'update' || $op == 'delete') {
      if ($op == 'update') {
        // Get permissions set prior to update.
        $permissions_original = $role->original->getPermissions();
        $removed_permissions = array_diff($permissions_original, $permissions);
      }
      elseif ($op == 'delete') {
        // If role is being deleted, then remove all its permissions.
        $removed_permissions = $permissions;
      }

      foreach ($removed_permissions as $removed_permission) {
        $id = $this->getPermissionMachineNameFromKey($removed_permission);
        $perm_entity = $storage->load($id);

        if (isset($perm_entity) && $perm_entity->permissionHasRole($role_id)) {
          $perm_entity->removeRoleFromPermission($role_id);

          // If permission is no longer assigned to any roles, then delete
          // the permission config entity.
          if (empty($perm_entity->getRoles())) {
            $perm_entity->delete();
          }
          // Otherwise save the permission config entity.
          else {
            $perm_entity->save();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncPermissionToRoles(string $permission): void {
    $config_name = $this->getPermissionMachineNameFromKey($permission);
    /** @var \Drupal\Core\Config\ImmutableConfig  */
    $config = $this->configFactory->get("$this->configPrefix.$config_name");
    $permission_label = $config->get('label');
    $permission_roles = $config->get('roles');

    // Loop through all roles; add/remove permission.
    $roles = user_roles();
    foreach ($roles as $role) {
      if (in_array($role->id(), $permission_roles)
        && !$role->hasPermission($permission_label)
        ) {

        $role->grantPermission($permission_label);
        $role->save();
      }
      elseif (!in_array($role->id(), $permission_roles)
      && $role->hasPermission($permission_label)
        ) {

        $role->revokePermission($permission_label);
        $role->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionMachineNameFromKey($name) {
    $name = preg_replace('/[^a-z0-9_]+/', '_', $name);
    return strtolower($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionKeyFromMachineName(string $machine_name) {
    /** @var \Drupal\Core\Config\ImmutableConfig */
    $config = $this->configFactory->get("$this->configPrefix.$machine_name");
    return $config->get('label');
  }

}
