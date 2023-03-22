<?php

namespace Drupal\Tests\features_permissions\Kernel;

use Drupal\features_permissions\Entity\Permission;

/**
 * Tests the Permission entity.
 *
 * @group features_permissions
 */
class PermissionEntityTest extends FeaturesPermissionsKernelTestBase {

  /**
   * Tests the Permission class.
   */
  public function testPermissionClass() {
    $perm_entity = Permission::create([
      'id' => 'test_permission_10',
      'label' => 'Test permission 10',
      'roles' => [
        'role_b',
      ],
    ]);
    $perm_entity->save();

    // Test ::getRoles() method.
    $this->assertSame($perm_entity->getRoles(), ['role_b']);
    // Test ::permissionHasRole() method.
    $this->assertTrue($perm_entity->permissionHasRole('role_b'));
    $this->assertFalse($perm_entity->permissionHasRole('role_a'));

    // Test ::addRoleToPermission() method.
    $perm_entity->addRoleToPermission('role_a');
    $this->assertSame($perm_entity->getRoles(), ['role_a', 'role_b']);

    // Test ::removeRoleFromPermission() method.
    $perm_entity->removeRoleFromPermission('role_a');
    $this->assertSame($perm_entity->getRoles(), ['role_b']);
  }

  /**
   * Tests dependency calculation for the Permission config entity type.
   */
  public function testPermissionCalculateDependencies() {
    // The field_ui module provides a permission with a dependency. Install to
    // ensure the 'administer user fields' permission dependencies are
    // property copied to the Permission config entity.
    $this->enableModules(['field', 'field_ui']);
    $this->installConfig(['field', 'field_ui']);

    $perm_entity = Permission::create([
      'id' => 'administer_user_fields',
      'label' => 'administer user fields',
      'roles' => [
        'role_b',
      ],
    ]);
    $perm_entity->save();

    $expected_dependencies = [
      'module' => [
        'user',
      ],
    ];
    $this->assertSame($perm_entity->calculateDependencies(), $expected_dependencies);
  }

}
