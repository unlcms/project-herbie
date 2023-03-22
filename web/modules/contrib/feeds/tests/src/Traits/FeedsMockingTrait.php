<?php

namespace Drupal\Tests\feeds\Traits;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Prophecy\Argument;

/**
 * Provides methods for mocking certain Feeds classes.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsMockingTrait {

  /**
   * Returns a mocked feed type entity.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   A mocked feed type entity.
   */
  protected function getMockFeedType() {
    $feed_type = $this->createMock(FeedTypeInterface::class);
    $feed_type->id = 'test_feed_type';
    $feed_type->description = 'This is a test feed type';
    $feed_type->label = 'Test feed type';
    $feed_type->expects($this->any())
      ->method('label')
      ->will($this->returnValue($feed_type->label));

    return $feed_type;
  }

  /**
   * Returns a mocked feed entity.
   *
   * @return \Drupal\feeds\FeedInterface
   *   A mocked feed entity.
   */
  protected function getMockFeed() {
    $feed = $this->createMock(FeedInterface::class);
    $feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->getMockFeedType()));

    return $feed;
  }

  /**
   * Returns a mocked AccountSwitcher object.
   *
   * The returned object verifies that if switchTo() is called, switchBack()
   * is also called.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   A mocked AccountSwitcher object.
   */
  protected function getMockedAccountSwitcher() {
    $switcher = $this->prophesize(AccountSwitcherInterface::class);

    $switcher->switchTo(Argument::type(AccountInterface::class))
      ->will(function () use ($switcher) {
        $switcher->switchBack()->shouldBeCalled();

        return $switcher->reveal();
      });

    return $switcher->reveal();
  }

  /**
   * Mocks an account object.
   *
   * @param array $perms
   *   The account's permissions.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The mocked acount object.
   */
  protected function getMockAccount(array $perms = []) {
    $account = $this->createMock(AccountInterface::class);
    if ($perms) {
      $map = [];
      foreach ($perms as $perm => $has) {
        $map[] = [$perm, $has];
      }
      $account->expects($this->any())
        ->method('hasPermission')
        ->will($this->returnValueMap($map));
    }

    return $account;
  }

  /**
   * Mocks a field definition.
   *
   * @param array $settings
   *   The field storage and instance settings.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   A mocked field definition.
   */
  protected function getMockFieldDefinition(array $settings = []) {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->expects($this->any())
      ->method('getSettings')
      ->will($this->returnValue($settings));

    return $definition;
  }

  /**
   * Mocks the file system.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   A mocked file system.
   */
  protected function getMockFileSystem() {
    $definition = $this->createMock(FileSystemInterface::class);
    $definition->expects($this->any())
      ->method('tempnam')
      ->will($this->returnCallback(function () {
        $args = func_get_args();
        $dir = $args[1];
        mkdir('vfs://feeds/' . $dir);
        $file = 'vfs://feeds/' . $dir . '/' . mt_rand(10, 1000);
        touch($file);
        return $file;
      }));
    return $definition;
  }

}
