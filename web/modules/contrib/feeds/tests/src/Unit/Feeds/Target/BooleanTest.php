<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Boolean;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Boolean
 * @group feeds
 */
class BooleanTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'boolean';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Boolean::class;
  }

  /**
   * Tests preparing a value.
   *
   * @param bool $expected
   *   The expected result.
   * @param mixed $value
   *   The input value.
   *
   * @covers ::prepareValue
   * @dataProvider valueProvider
   */
  public function testPrepareValue(bool $expected, $value) {
    $target = $this->instantiatePlugin();
    $values = ['value' => $value];

    $method = $this->getProtectedClosure($target, 'prepareValue');
    $method(0, $values);
    $this->assertSame($expected, $values['value']);
  }

  /**
   * Data provider for testPrepareValue().
   */
  public function valueProvider() {
    return [
      [
        'expected' => TRUE,
        'value' => 'string',
      ],
      [
        'expected' => FALSE,
        'value' => '0',
      ],
      [
        'expected' => TRUE,
        'value' => ' 1 ',
      ],
      [
        'expected' => FALSE,
        'value' => ' 0 ',
      ],
      [
        'expected' => TRUE,
        'value' => 1,
      ],
      [
        'expected' => FALSE,
        'value' => 0,
      ],
      [
        'expected' => TRUE,
        'value' => TRUE,
      ],
      [
        'expected' => FALSE,
        'value' => FALSE,
      ],
      [
        'expected' => FALSE,
        'value' => [],
      ],
      [
        'expected' => TRUE,
        'value' => [1],
      ],
      [
        'expected' => FALSE,
        'value' => [0],
      ],
      [
        'expected' => TRUE,
        'value' => [[1]],
      ],
      [
        'expected' => FALSE,
        'value' => [[0]],
      ],
    ];
  }

}
