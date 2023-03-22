<?php

namespace Drupal\Tests\features_permissions\Kernel;

/**
 * Tests role entity CRUD operations.
 *
 * @group features_permissions
 */
class RoleCrudTest extends FeaturesPermissionsKernelTestBase {

  /**
   * Tests role entity CRUD operations.
   */
  public function testCrud() {
    $permission_storage = \Drupal::service('entity_type.manager')
      ->getStorage('user_permission');
    $role_storage = \Drupal::service('entity_type.manager')
      ->getStorage('user_role');

    // Tests features_permissions_entity_insert().
    $this->assertSame(
      $permission_storage
        ->load('test_permission_1')
        ->getRoles(),
      [
        'role_a',
      ]
    );

    // Tests features_permissions_entity_update().
    $this->roleB
      ->grantPermission('test permission 1')
      ->save();

    $this->assertSame(
      $permission_storage
        ->load('test_permission_1')
        ->getRoles(),
      [
        'role_a',
        'role_b',
      ]
    );

    // Tests features_permissions_entity_update() for the authenticated role.
    $auth_role = $role_storage->load('authenticated');
    $auth_role
      ->grantPermission('test permission 1')
      ->save();

    $this->assertSame(
      $permission_storage
        ->load('test_permission_1')
        ->getRoles(),
      [
        'authenticated',
        'role_a',
        'role_b',
      ]
    );

    // Tests features_permissions_entity_update() for the anonymous role.
    $anon_role = $role_storage->load('anonymous');
    $anon_role
      ->grantPermission('test permission 1')
      ->save();

    $this->assertSame(
      $permission_storage
        ->load('test_permission_1')
        ->getRoles(),
      [
        'anonymous',
        'authenticated',
        'role_a',
        'role_b',
      ]
    );

    // Tests features_permissions_entity_delete().
    $this->roleB->delete();

    $this->assertSame(
      $permission_storage
        ->load('test_permission_1')
        ->getRoles(),
      [
        'anonymous',
        'authenticated',
        'role_a',
      ]
    );
  }

}
