<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Number;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Number
 * @group feeds
 */
class NumberTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'number';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Number::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'string'];
    $method(0, $values);
    $this->assertSame($values['value'], '');

    $values = ['value' => '10'];
    $method(0, $values);
    $this->assertSame($values['value'], '10');
  }

}
