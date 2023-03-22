<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\feeds\FeedHandlerBase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\feeds\FeedHandlerBase
 * @group feeds
 */
class FeedHandlerBaseTest extends FeedsUnitTestCase {

  /**
   * Tests if an instance of FeedHandlerBase can be created.
   *
   * @covers ::__construct
   * @covers ::createInstance
   */
  public function testConstruct() {
    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $this->createMock(EventDispatcherInterface::class));
    $container->set('database', $this->createMock(Connection::class));

    $mock = $this->getMockForAbstractClass(FeedHandlerBase::class, [], '', FALSE);
    $mock_class = get_class($mock);
    $handler = $mock_class::createInstance($container, $this->createMock(EntityTypeInterface::class));
    $this->assertInstanceOf(FeedHandlerBase::class, $handler);
  }

}
