<?php

namespace Drupal\Tests\features_permissions\Kernel;

/**
 * Tests the Permission Manager service.
 *
 * @group features_permissions
 */
class PermissionManagerTest extends FeaturesPermissionsKernelTestBase {

  /**
   * The Permission Manager service.
   *
   * @var \Drupal\features_permissions\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->permissionManager = \Drupal::service('features_permissions.permission_manager');
  }

  /*
   * ::syncRoleToPermissions() is tested in RoleCrudTest
   * ::syncPermissionToRoles() is tested in FeaturesCrudTest
   */

  /**
   * Tests the ::getPermissionMachineNameFromKey method.
   *
   * @dataProvider permissionKeyDataProvider
   */
  public function testGetPermissionMachineNameFromKey($key, $machine_name) {
    $this->assertEquals(
      $this->permissionManager
        ->getPermissionMachineNameFromKey($key),
      $machine_name
    );
  }

  /**
   * Data provider for ::testGetPermissionMachineNameFromKey().
   */
  public function permissionKeyDataProvider() {
    return [
      [
        'a permission',
        'a_permission',
      ],
      [
        'another $permission',
        'another_permission',
      ],
      [
        'yet another permission',
        'yet_another_permission',
      ],
      [
        'yet another          permission',
        'yet_another_permission',
      ],
    ];
  }

  /**
   * Tests the ::getPermissionKeyFromMachineName method.
   *
   * @dataProvider permissionMachineNameDataProvider
   */
  public function testGetPermissionKeyFromMachineName($machine_name, $key) {
    $this->assertEquals(
      $this->permissionManager
        ->getPermissionKeyFromMachineName($machine_name),
      $key
    );
  }

  /**
   * Data provider for ::testGetPermissionKeyFromMachineName().
   */
  public function permissionMachineNameDataProvider() {
    return [
      [
        'test_permission_1',
        'test permission 1',
      ],
      [
        'test_permission_2',
        'test permission 2',
      ],
    ];
  }

}
