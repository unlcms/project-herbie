<?php

namespace Drupal\Tests\features_permissions\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Base test class for Features Permissions kernel tests.
 *
 * @group features_permissions
 */
abstract class FeaturesPermissionsKernelTestBase extends KernelTestBase {

  /**
   * Test Role A.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $roleA;

  /**
   * Test Role B.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $roleB;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'features_permissions',
    'features_permissions_test',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user_permission');
    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installConfig(['features_permissions', 'user']);
    $this->installConfig('system');

    $this->roleA = Role::create([
      'id' => 'role_a',
      'label' => 'Role A',
      'weight' => 0,
      'is_admin' => FALSE,
      'permissions' => [
        'test permission 1',
      ],
    ]);
    $this->roleA->save();

    $this->roleB = Role::create([
      'id' => 'role_b',
      'label' => 'Role B',
      'weight' => 0,
      'is_admin' => FALSE,
      'permissions' => [
        'test permission 2',
      ],
    ]);
    $this->roleB->save();
  }

}
