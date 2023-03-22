<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\user\UserInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\UserRole
 * @group feeds
 */
class UserRoleTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'user',
    'feeds',
    'feeds_test_alter_source',
  ];

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'name' => 'name',
      'mail' => 'mail',
      'role_ids' => 'role_ids',
      'role_labels' => 'role_labels',
    ], [
      'id' => 'user_import',
      'processor' => 'entity:user',
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'authorize' => FALSE,
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'name'],
        ],
        [
          'target' => 'mail',
          'map' => ['value' => 'mail'],
          'unique' => ['value' => TRUE],
        ],
      ],
    ]);

    $this->userStorage = $this->container->get('entity_type.manager')->getStorage('user');
    $this->roleStorage = $this->container->get('entity_type.manager')->getStorage('user_role');
  }

  /**
   * Asserts that the given user has the given role.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to check for the role.
   * @param string $rid
   *   The expected role ID that the user should have.
   * @param string $message
   *   (optional) Assertion message.
   */
  protected function assertHasRole(UserInterface $account, $rid, $message = '') {
    $this->assertTrue($account->hasRole($rid), $message);
  }

  /**
   * Asserts that the given user NOT has the given role.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to check for the role.
   * @param string $rid
   *   The expected role ID that the user should NOT have.
   * @param string $message
   *   (optional) Assertion message.
   */
  protected function assertNotHasRole(UserInterface $account, $rid, $message = '') {
    $this->assertFalse($account->hasRole($rid), $message);
  }

  /**
   * Asserts the expected number of roles an user has.
   *
   * This excludes the authenticated user role.
   *
   * @param int $expected_number_of_roles
   *   The expected number of roles.
   * @param \Drupal\user\UserInterface $account
   *   The account to check for the role count.
   * @param string $message
   *   (optional) Assertion message.
   */
  protected function assertRoleCount($expected_number_of_roles, UserInterface $account, $message = '') {
    $this->assertEquals($expected_number_of_roles, count($account->getRoles(TRUE)), $message);
  }

  /**
   * Tests mapping to role without automatically creating new roles.
   */
  public function testWithoutRoleCreation() {
    // Create the manager role.
    $this->createRole([], 'manager');

    // Add mapping to role.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_ids'],
    ]);
    $this->feedType->save();

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Morticia did not get any roles.
    $account = user_load_by_name('Morticia');
    $this->assertNotHasRole($account, 'editor', 'Morticia does not have the editor role.');
    $this->assertRoleCount(0, $account, 'Morticia has no special roles.');

    // Assert that Fester got the manager role and one role in total.
    $account = user_load_by_name('Fester');
    $this->assertHasRole($account, 'manager', 'Fester has the manager role.');
    $this->assertRoleCount(1, $account, 'Fester has one role.');

    // Assert that Gomez got the manager role but not the tester role, since
    // that role doesn't exist on the system.
    $account = user_load_by_name('Gomez');
    $this->assertHasRole($account, 'manager', 'Gomez has the manager role.');
    $this->assertNotHasRole($account, 'tester', 'Gomez does not have the tester role.');
    $this->assertRoleCount(1, $account, 'Gomez has one role.');

    // Assert that Pugsley has no roles.
    $account = user_load_by_name('Pugsley');
    $this->assertRoleCount(0, $account, 'Pugsley has no special roles.');

    // Assert that only one role exists:
    // - manager.
    $roles = $this->roleStorage->loadMultiple();
    $this->assertEquals(1, count($roles), 'Only one role exists.');
  }

  /**
   * Tests mapping to role with automatically creating new roles.
   */
  public function testWithRoleCreation() {
    // Create the manager role.
    $this->createRole([], 'manager');

    // Add mapping to role.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_ids'],
      'settings' => [
        'autocreate' => TRUE,
      ],
    ]);
    $this->feedType->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Morticia got the editor role and one role in total.
    $account = user_load_by_name('Morticia');
    $this->assertHasRole($account, 'editor', 'Morticia has the editor role.');
    $this->assertRoleCount(1, $account, 'Morticia has one role.');

    // Assert that Fester got the manager role and one role in total.
    $account = user_load_by_name('Fester');
    $this->assertHasRole($account, 'manager', 'Fester has the manager role.');
    $this->assertRoleCount(1, $account, 'Fester has one role.');

    // Assert that Gomez got the manager, the editor role and two roles in
    // total.
    $account = user_load_by_name('Gomez');
    $this->assertHasRole($account, 'manager', 'Gomez has the manager role.');
    $this->assertHasRole($account, 'tester', 'Gomez has the tester role.');
    $this->assertRoleCount(2, $account, 'Gomez has two roles.');

    // Assert that Pugsley has no roles.
    $account = user_load_by_name('Pugsley');
    $this->assertRoleCount(0, $account, 'Pugsley has no special roles.');

    // Assert that three roles exist:
    // - manager;
    // - editor;
    // - tester.
    $roles = $this->roleStorage->loadMultiple();
    $this->assertEquals(3, count($roles), 'Three roles exist.');

    // Assert that the roles all got the expected label.
    $this->assertEquals('editor', $roles['editor']->label());
    $this->assertEquals('tester', $roles['tester']->label());
  }

  /**
   * Tests automatically creating new roles based on label.
   */
  public function testRoleCreationUsingLabel() {
    // Add mapping to role.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_labels'],
      'settings' => [
        'reference_by' => 'label',
        'autocreate' => TRUE,
      ],
    ]);
    $this->feedType->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Morticia got the Article Editor role.
    $account = user_load_by_name('Morticia');
    $this->assertHasRole($account, 'article_editor', 'Morticia got the Article Editor role.');
    $this->assertRoleCount(1, $account, 'Morticia has one role.');

    // Assert that Gomez got the manager and tester roles.
    $account = user_load_by_name('Gomez');
    $this->assertHasRole($account, 'account_manager', 'Gomez has the manager role.');
    $this->assertHasRole($account, 'software_tester', 'Gomez has the tester role.');
    $this->assertRoleCount(2, $account, 'Gomez has two roles.');

    // Assert that the roles all got the expected label.
    $roles = $this->roleStorage->loadMultiple();
    $this->assertEquals(3, count($roles), 'Three roles exist.');
    $this->assertEquals('Article Editor', $roles['article_editor']->label());
    $this->assertEquals('Account Manager', $roles['account_manager']->label());
    $this->assertEquals('Software Tester', $roles['software_tester']->label());
  }

  /**
   * Tests mapping to role by role label.
   */
  public function testImportByRoleLabels() {
    // Create the manager and tester roles.
    $this->createRole([], 'account_manager', 'Account Manager');
    $this->createRole([], 'software_tester', 'Software Tester');

    // Add mapping to role.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_labels'],
      'settings' => [
        'reference_by' => 'label',
      ],
    ]);
    $this->feedType->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Morticia did not get any roles.
    $account = user_load_by_name('Morticia');
    $this->assertNotHasRole($account, 'editor', 'Morticia does not have the editor role.');
    $this->assertRoleCount(0, $account, 'Morticia has no special roles.');

    // Assert that Fester got the manager role and one roles in total.
    $account = user_load_by_name('Fester');
    $this->assertHasRole($account, 'account_manager', 'Fester has the manager role.');
    $this->assertRoleCount(1, $account, 'Fester has one role.');

    // Assert that Gomez got the manager and tester roles.
    $account = user_load_by_name('Gomez');
    $this->assertHasRole($account, 'account_manager', 'Gomez has the manager role.');
    $this->assertHasRole($account, 'software_tester', 'Gomez has the tester role.');
    $this->assertRoleCount(2, $account, 'Gomez has two roles.');

    // Assert that Pugsley has no roles.
    $account = user_load_by_name('Pugsley');
    $this->assertRoleCount(0, $account, 'Pugsley has no special roles.');

    // Assert that two roles exist:
    // - manager;
    // - tester.
    $roles = $this->roleStorage->loadMultiple();
    $this->assertEquals(2, count($roles), 'Two roles exist.');
  }

  /**
   * Tests mapping to role using only allowed roles.
   */
  public function testWithAllowedRoles() {
    // Create the manager and editor roles.
    $this->createRole([], 'manager');
    $this->createRole([], 'editor');

    // Add mapping to role. The manager role may not be assigned to the user by
    // the feed.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_ids'],
      'settings' => [
        'allowed_roles' => [
          'manager' => FALSE,
          'editor' => 'editor',
        ],
        'autocreate' => TRUE,
      ],
    ]);
    $this->feedType->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Morticia got the editor role and one role in total.
    $account = user_load_by_name('Morticia');
    $this->assertHasRole($account, 'editor', 'Morticia has the editor role.');
    $this->assertRoleCount(1, $account, 'Morticia has one role.');

    // Assert that Fester did not got the manager role, because that role was
    // not an allowed value.
    $account = user_load_by_name('Fester');
    $this->assertNotHasRole($account, 'manager', 'Fester does not have the manager role.');
    $this->assertRoleCount(0, $account, 'Fester has no special roles.');

    // Assert that Gomez only got the tester role and not the manager role.
    $account = user_load_by_name('Gomez');
    $this->assertNotHasRole($account, 'manager', 'Gomez does not have the manager role.');
    $this->assertHasRole($account, 'tester', 'Gomez has the tester role.');
    $this->assertRoleCount(1, $account, 'Gomez has one role.');
  }

  /**
   * Tests that roles can be revoked and that only allowed roles are revoked.
   */
  public function testRevokeRoles() {
    // Create the manager, editor and tester roles.
    $this->createRole([], 'manager');
    $this->createRole([], 'editor');
    $this->createRole([], 'tester');

    // Add mapping to role. The manager role may not be revoked, but the editor
    // role may.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_ids'],
      'settings' => [
        'allowed_roles' => [
          'manager' => FALSE,
          'editor' => 'editor',
          'tester' => 'tester',
        ],
        'revoke_roles' => TRUE,
      ],
    ]);
    $this->feedType->save();

    // Create account for Morticia with roles "manager" and "editor". In the
    // source only "editor" is specified. Morticia should keep both roles.
    $this->userStorage->create([
      'name' => 'Morticia',
      'mail' => 'morticia@example.com',
      'pass' => 'mort',
      'status' => 1,
      'roles' => [
        'manager',
        'editor',
      ],
    ])->save();
    // Create account for Pugsley with roles "manager", "editor" and "tester".
    // Pugsley has no roles in the source so should only keep the "manager"
    // role.
    $this->userStorage->create([
      'name' => 'Pugsley',
      'mail' => 'pugsley@example.com',
      'pass' => 'pugs',
      'status' => 1,
      'roles' => [
        'manager',
        'editor',
        'tester',
      ],
    ])->save();
    // Create account for Gomez and give it the "editor" role. Gomez has roles
    // "tester" and "manager" in the source, so it should lose the "editor" role
    // and gain the "tester" role.
    $this->userStorage->create([
      'name' => 'Gomez',
      'mail' => 'gomez@example.com',
      'pass' => 'gome',
      'status' => 1,
      'roles' => [
        'editor',
      ],
    ])->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Morticia kept the manager and editor roles.
    $account = user_load_by_name('Morticia');
    $this->assertHasRole($account, 'manager', 'Morticia still has the manager role.');
    $this->assertHasRole($account, 'editor', 'Morticia has the editor role.');
    $this->assertRoleCount(2, $account, 'Morticia has two roles.');

    // Assert that Pugsley only kept the manager role.
    $account = user_load_by_name('Pugsley');
    $this->assertHasRole($account, 'manager', 'Pugsley still has the manager role.');
    $this->assertNotHasRole($account, 'editor', 'Pugsley no longer has the editor role.');
    $this->assertNotHasRole($account, 'tester', 'Pugsley no longer has the tester role.');
    $this->assertRoleCount(1, $account, 'Pugsley has one role.');

    // Assert that Gomez lost the editor role, and gained the tester role.
    $account = user_load_by_name('Gomez');
    $this->assertNotHasRole($account, 'editor', 'Gomez no longer has the editor role.');
    $this->assertHasRole($account, 'tester', 'Gomez has the tester role.');
    $this->assertRoleCount(1, $account, 'Gomez has one role.');
  }

  /**
   * Tests if no roles are revoked if the option "Revoke roles" is disabled.
   */
  public function testNoRevokeRoles() {
    // Create the manager and editor roles.
    $this->createRole([], 'manager');
    $this->createRole([], 'editor');

    // Add mapping to role. Set option to not revoke roles.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_ids'],
      'settings' => [
        'allowed_roles' => [
          'manager' => FALSE,
          'editor' => 'editor',
        ],
        'revoke_roles' => FALSE,
      ],
    ]);
    $this->feedType->save();

    // Create account for Pugsley with roles "manager" and "editor". Pugsley has
    // no roles in the source file, but roles should not be revoked, so Pugsley
    // should keep all roles.
    $this->userStorage->create([
      'name' => 'Pugsley',
      'mail' => 'pugsley@example.com',
      'pass' => 'pugs',
      'status' => 1,
      'roles' => [
        'manager',
        'editor',
      ],
    ])->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    // Assert that Pugsley kept all roles.
    $account = user_load_by_name('Pugsley');
    $this->assertHasRole($account, 'manager', 'Pugsley still has the manager role.');
    $this->assertHasRole($account, 'editor', 'Pugsley still has the editor role.');
    $this->assertRoleCount(2, $account, 'Pugsley has two roles.');
  }

  /**
   * Tests updating a user with an existing role.
   */
  public function testImportWithExistingRole() {
    // Create a user with the editor role.
    $this->createRole([], 'editor');
    $user = $this->createUser([
      'name' => 'Morticia',
    ]);
    $user->addRole('editor');
    $user->save();

    // Add mapping to role.
    $this->feedType->addMapping([
      'target' => 'roles',
      'map' => ['target_id' => 'role_ids'],
      'settings' => [
        'allowed_roles' => [
          'editor' => 'editor',
        ],
      ],
    ]);
    $this->feedType->save();

    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users_roles.csv',
    ]);
    $feed->import();

    /** @var \Drupal\user\Entity\User $account */
    $account = user_load_by_name('Morticia');

    // Assert that the editor role is still there.
    $this->assertHasRole($account, 'editor');
    // Assert that there is only 1 role, editor.
    $this->assertRoleCount(1, $account);
  }

}
