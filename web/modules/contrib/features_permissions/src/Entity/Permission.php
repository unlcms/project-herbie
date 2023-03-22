<?php

namespace Drupal\features_permissions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Permission config entity type.
 *
 * @ConfigEntityType(
 *   id = "user_permission",
 *   label = @Translation("Permission"),
 *   label_collection = @Translation("Permissions"),
 *   label_singular = @Translation("permission"),
 *   label_plural = @Translation("permissions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count permission",
 *     plural = "@count permissions",
 *   ),
 *   config_prefix = "permission",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "roles"
 *   }
 * )
 */
class Permission extends ConfigEntityBase {

  /**
   * The machine name of this permission.
   *
   * The ID is only used for config entity storage.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of this permission.
   *
   * The label is used as the key for Drupal's permission system.
   *
   * @var string
   */
  protected $label;

  /**
   * Roles to which this permission is granted.
   *
   * @var array
   */
  protected $roles;

  /**
   * Get all roles with this permission.
   *
   * @return array
   *   An array of role machine names.
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * Checks if a permission has a role.
   *
   * @param string $role
   *   The machine name of a role.
   *
   * @return bool
   *   Whether or not the permission has the role.
   */
  public function permissionHasRole(string $role) {
    if (in_array($role, $this->roles)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds a role to a permission.
   *
   * @param string $role
   *   The machine name of a role.
   */
  public function addRoleToPermission(string $role): void {
    if (in_array($role, $this->roles)) {
      return;
    }
    $this->roles[] = $role;
    sort($this->roles);
  }

  /**
   * Removes a role from a permission.
   *
   * @param string $role
   *   The machine name of a role.
   */
  public function removeRoleFromPermission(string $role): void {
    if (($key = array_search($role, $this->roles)) !== FALSE) {
      unset($this->roles[$key]);
      // Re-index the array.
      $this->roles = array_values($this->roles);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Copy dependencies from permission.
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $dependencies = $permissions[$this->label]['dependencies'] ?? [];
    foreach ($dependencies as $category => $cat_dependencies) {
      foreach ($cat_dependencies as $cat_dependency) {
        $this->addDependency($category, $cat_dependency);
      }
    }

    return $this->dependencies;
  }

}
