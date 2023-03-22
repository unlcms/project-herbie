<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\Feeds\Target\Path;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Path
 * @group feeds
 */
class PathTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'path';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Path::class;
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

    $definition->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinition')
      ->will($this->returnValue($this->createMock(FieldStorageDefinitionInterface::class)));

    return $definition;
  }

  /**
   * @covers ::prepareValue
   *
   * @param string $expected
   *   The expected path.
   * @param array $values
   *   The values passed to the prepareValue() method.
   *
   * @dataProvider valuesProvider
   */
  public function testPrepareValue($expected, array $values) {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $method(0, $values);
    $this->assertSame($expected, $values['alias']);
  }

  /**
   * Data provider for ::testPrepareValue().
   */
  public function valuesProvider() {
    return [
      'without-slash' => [
        'expected' => '/path',
        'values' => ['alias' => 'path '],
      ],
      'with-slash' => [
        'expected' => '/foo',
        'values' => ['alias' => '/foo '],
      ],
      'starting-with-space' => [
        'expected' => '/bar',
        'values' => ['alias' => ' bar'],
      ],
      'starting-with-space-and-with-slash' => [
        'expected' => '/qux',
        'values' => ['alias' => ' /qux'],
      ],
      'already-correctly-formatted' => [
        'expected' => '/foo-bar',
        'values' => ['alias' => '/foo-bar'],
      ],
    ];
  }

}
