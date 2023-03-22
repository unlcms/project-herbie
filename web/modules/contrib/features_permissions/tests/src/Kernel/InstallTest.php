<?php

namespace Drupal\Tests\features_permissions\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests module installation.
 *
 * @group features_permissions
 */
class InstallTest extends KernelTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
  ];

  /**
   * Tests features_permissions_install().
   */
  public function testInstall() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installConfig(['user']);
    $this->installConfig('system');

    $this->roleA = Role::create([
      'id' => 'role_a',
      'label' => 'Role A',
      'weight' => 0,
      'is_admin' => FALSE,
      'permissions' => [
        'cancel account',
        'change own username',
      ],
    ]);
    $this->roleA->save();

    $this->roleB = Role::create([
      'id' => 'role_b',
      'label' => 'Role B',
      'weight' => 0,
      'is_admin' => FALSE,
      'permissions' => [
        'cancel account',
      ],
    ]);
    $this->roleB->save();

    $this->enableModules(['features_permissions']);
    $this->installEntitySchema('user_permission');
    $this->installConfig(['features_permissions']);

    \Drupal::moduleHandler()->loadInclude('features_permissions', 'install');
    features_permissions_install();

    $permission_storage = \Drupal::service('entity_type.manager')
      ->getStorage('user_permission');

    $this->assertSame(
      $permission_storage
        ->load('cancel_account')
        ->getRoles(),
      [
        'role_a',
        'role_b',
      ]
    );

    $this->assertSame(
      $permission_storage
        ->load('change_own_username')
        ->getRoles(),
      [
        'role_a',
      ]
    );
  }

}
