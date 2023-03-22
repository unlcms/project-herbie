<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Item;

use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Base class for item tests.
 */
abstract class ItemTestBase extends FeedsUnitTestCase {

  /**
   * The item under test.
   *
   * @var \Drupal\feeds\Feeds\Item\ItemInterface
   */
  protected $item;

  /**
   * Tests if the item is implementing the expected interface.
   */
  public function testImplementingInterface() {
    $this->assertInstanceOf(ItemInterface::class, $this->item);
  }

  /**
   * @covers ::set
   * @covers ::get
   */
  public function testSetAndGet() {
    $this->assertSame($this->item, $this->item->set('field', 'value'));
    $this->assertSame('value', $this->item->get('field'));
  }

  /**
   * @covers ::toArray
   * @covers ::set
   */
  public function testToArray() {
    $this->item->set('field', 'value');
    $this->item->set('field2', 'value2');

    $expected = [
      'field' => 'value',
      'field2' => 'value2',
    ];
    $this->assertEquals($expected, $this->item->toArray());
  }

  /**
   * @covers ::fromArray
   * @covers ::get
   */
  public function testFromArray() {
    $this->assertSame($this->item, $this->item->fromArray([
      'Foo' => 'Bar',
      'Baz' => 'Qux',
    ]));

    $this->assertSame('Bar', $this->item->get('Foo'));
    $this->assertSame('Qux', $this->item->get('Baz'));
    $this->assertNull($this->item->get('Bar'));
    $this->assertNull($this->item->get('Qux'));
  }

}
