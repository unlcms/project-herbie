<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Language\Language;
use Drupal\feeds\FeedAccessControlHandler;

/**
 * @coversDefaultClass \Drupal\feeds\FeedAccessControlHandler
 * @group feeds
 */
class FeedAccessControlHandlerTest extends FeedsUnitTestCase {

  /**
   * Metadata class for the feed entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The handler to test.
   *
   * @var \Drupal\feeds\FeedAccessControlHandler
   */
  protected $controller;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->once())
      ->method('id')
      ->will($this->returnValue('feeds_feed'));
    $this->controller = new FeedAccessControlHandler($this->entityType);
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValue([]));
    $this->controller->setModuleHandler($this->moduleHandler);
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $feed = $this->createMock('\Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('feed_bundle'));
    $feed->expects($this->any())
      ->method('language')
      ->will($this->returnValue(new Language()));

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');

    $this->assertFalse($this->controller->access($feed, 'beep', $account));
    $this->assertFalse($this->controller->access($feed, 'unlock', $account));

    $this->controller->resetCache();

    $this->assertFalse($this->controller->access($feed, 'unlock', $account));

    $account->expects($this->any())
      ->method('hasPermission')
      ->with($this->equalTo('administer feeds'))
      ->will($this->returnValue(TRUE));

    $this->assertTrue($this->controller->access($feed, 'clear', $account));
    $this->assertTrue($this->controller->access($feed, 'view', $account));

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');

    $account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with($this->logicalOr(
           $this->equalTo('administer feeds'),
           $this->equalTo('delete feed_bundle feeds')
       ))
      ->will($this->onConsecutiveCalls(FALSE, TRUE));
    $this->assertTrue($this->controller->access($feed, 'delete', $account));
  }

  /**
   * @covers ::createAccess
   */
  public function testCheckCreateAccess() {
    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');

    $account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with($this->logicalOr(
           $this->equalTo('administer feeds'),
           $this->equalTo('create feed_bundle feeds')
       ))
      ->will($this->onConsecutiveCalls(FALSE, FALSE));
    $this->assertFalse($this->controller->createAccess('feed_bundle', $account));

    $this->controller->resetCache();

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with($this->logicalOr(
           $this->equalTo('administer feeds'),
           $this->equalTo('create feed_bundle feeds')
       ))
      ->will($this->onConsecutiveCalls(FALSE, TRUE));
    $this->assertTrue($this->controller->createAccess('feed_bundle', $account));
  }

}
