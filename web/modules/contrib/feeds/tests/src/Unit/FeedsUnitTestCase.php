<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Tests\feeds\Traits\FeedsMockingTrait;
use Drupal\Tests\feeds\Traits\FeedsReflectionTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use org\bovigo\vfs\vfsStream;

/**
 * Base class for Feeds unit tests.
 */
abstract class FeedsUnitTestCase extends UnitTestCase {

  use FeedsMockingTrait;
  use FeedsReflectionTrait;
  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->defineConstants();
    vfsStream::setup('feeds');
  }

  /**
   * Returns the absolute directory path of the Feeds module.
   *
   * @return string
   *   The absolute path to the Feeds module.
   */
  protected function absolutePath() {
    return dirname(dirname(dirname(__DIR__)));
  }

  /**
   * Returns the absolute directory path of the resources folder.
   *
   * @return string
   *   The absolute path to the resources folder.
   */
  protected function resourcesPath() {
    return $this->absolutePath() . '/tests/resources';
  }

  /**
   * Returns a mock stream wrapper manager.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperManager
   *   A mocked stream wrapper manager.
   */
  protected function getMockStreamWrapperManager() {
    $mock = $this->createMock(StreamWrapperManager::class, [], [], '', FALSE);

    $wrappers = [
      'vfs' => 'VFS',
      'public' => 'Public',
    ];

    $mock->expects($this->any())
      ->method('getDescriptions')
      ->will($this->returnValue($wrappers));

    $mock->expects($this->any())
      ->method('getWrappers')
      ->will($this->returnValue($wrappers));

    return $mock;
  }

  /**
   * Defines stub constants.
   */
  protected function defineConstants() {
    if (!defined('FILE_STATUS_PERMANENT')) {
      define('FILE_STATUS_PERMANENT', 1);
    }
  }

}
