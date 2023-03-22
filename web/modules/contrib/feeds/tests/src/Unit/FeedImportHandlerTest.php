<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedImportHandler;
use Drupal\feeds\FeedsExecutableInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\FeedImportHandler
 * @group feeds
 */
class FeedImportHandlerTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The handler to test.
   *
   * @var \Drupal\feeds\FeedImportHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->handler = $this->getMockBuilder(FeedImportHandler::class)
      ->setConstructorArgs([
        $this->dispatcher,
        $this->createMock(Connection::class),
      ])
      ->setMethods(['getRequestTime', 'getExecutable'])
      ->getMock();
    $this->handler->setStringTranslation($this->getStringTranslationStub());
    $this->handler->expects($this->any())
      ->method('getRequestTime')
      ->willReturn(time());

    $this->feed = $this->createMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('id')
      ->will($this->returnValue(10));
    $this->feed->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_feed'));
  }

  /**
   * @covers ::import
   */
  public function testImport() {
    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));

    $this->handler->import($this->feed);
  }

  /**
   * @covers ::startBatchImport
   */
  public function testStartBatchImport() {
    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));

    $this->handler->startBatchImport($this->feed);
  }

  /**
   * @covers ::startCronImport
   */
  public function testStartCronImport() {
    $this->feed->expects($this->once())
      ->method('isLocked')
      ->will($this->returnValue(FALSE));

    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));

    $this->handler->startCronImport($this->feed);
  }

  /**
   * @covers ::pushImport
   */
  public function testPushImport() {
    $this->handler->expects($this->once())
      ->method('getExecutable')
      ->willReturn($this->createMock(FeedsExecutableInterface::class));
    $this->feed->expects($this->once())
      ->method('lock')
      ->will($this->returnValue($this->feed));

    $file = $this->resourcesPath() . '/csv/example.csv';
    $this->handler->pushImport($this->feed, file_get_contents($file), $this->createMock(FileSystemInterface::class));
  }

}
