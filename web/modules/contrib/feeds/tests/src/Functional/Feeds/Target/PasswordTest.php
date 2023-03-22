<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Target;

use Drupal\feeds\Feeds\Target\Password;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Password
 * @group feeds
 */
class PasswordTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'user',
  ];

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a feed type for importing users with passwords.
    $this->feedType = $this->createFeedTypeForCsv([
      'name' => 'name',
      'mail' => 'mail',
      'status' => 'status',
    ], [
      'processor' => 'entity:user',
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
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
        [
          'target' => 'status',
          'map' => ['value' => 'status'],
        ],
      ],
    ]);

    $this->userStorage = $this->container->get('entity_type.manager')->getStorage('user');
  }

  /**
   * Tests if users with passwords can login after import.
   *
   * @param string $source
   *   The CSV field to import.
   * @param array $settings
   *   The settings for the password target.
   *
   * @dataProvider providerPasswordTypes
   */
  public function test($source, array $settings = []) {
    $this->feedType->addCustomSource($source, [
      'value' => $source,
    ]);

    $this->feedType->addMapping([
      'target' => 'pass',
      'map' => ['value' => $source],
      'settings' => $settings,
    ]);
    $this->feedType->save();

    // Create an account for Gomez, to ensure passwords can also be imported for
    // existing users. Give Gomez a password different from the one that gets
    // imported to ensure that their password gets updated.
    $this->userStorage->create([
      'name' => 'Gomez',
      'mail' => 'gomez@example.com',
      'pass' => 'temporary',
      'status' => 1,
    ])->save();

    // Create a feed and import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/users.csv',
    ]);
    $this->batchImport($feed);

    // Assert result.
    $this->assertSession()->pageTextContains('Created 2 users');
    $this->assertSession()->pageTextContains('Updated 1 user');

    // Try to login as each successful imported user.
    $this->feedsLoginUser('Morticia', 'mort');
    $this->feedsLoginUser('Fester', 'fest');
    $this->feedsLoginUser('Gomez', 'gome');
  }

  /**
   * Data provider for ::test().
   */
  public function providerPasswordTypes() {
    return [
      'plain' => [
        'source' => 'password',
      ],
      'md5' => [
        'source' => 'password_md5',
        'settings' => ['pass_encryption' => Password::PASS_MD5],
      ],
      'sha512' => [
        'source' => 'password_sha512',
        'settings' => ['pass_encryption' => Password::PASS_SHA512],
      ],
    ];
  }

  /**
   * Logs in an imported user.
   *
   * @param string $username
   *   The user's username.
   * @param string $password
   *   The user's password.
   */
  protected function feedsLoginUser($username, $password) {
    $account = user_load_by_name($username);
    $this->assertNotEmpty($account, 'Imported user account loaded.');
    $account->passRaw = $password;
    $this->drupalLogin($account);
  }

}
