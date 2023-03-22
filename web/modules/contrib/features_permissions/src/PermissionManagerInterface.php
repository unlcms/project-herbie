<?php

namespace Drupal\features_permissions;

use Drupal\user\RoleInterface;

/**
 * An interface for the Permission Manager service.
 */
interface PermissionManagerInterface {

  /**
   * Syncs permissions from a given role to permission config entities.
   *
   * @param \Drupal\user\RoleInterface $role
   *   A role object.
   * @param string $op
   *   The operation (insert, update, delete).
   */
  public function syncRoleToPermissions(RoleInterface $role, string $op): void;

  /**
   * Syncs roles from a given permission to role config entities.
   *
   * @param string $permission
   *   The permission key (i.e. with spaces).
   */
  public function syncPermissionToRoles(string $permission): void;

  /**
   * Converts a permission name into a machine name.
   *
   * @param string $name
   *   A permission name.
   *
   * @return string
   *   A machine name for the permission config entity.
   */
  public function getPermissionMachineNameFromKey($name);

  /**
   * Get permission key (i.e. with spaces) from config entity's machine name.
   *
   * @param string $machine_name
   *   The machine name of a permission config entity.
   *
   * @return string
   *   The permission key (i.e. with spaces).
   */
  public function getPermissionKeyFromMachineName(string $machine_name);

}
