<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Feeds\Target\File;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\File
 * @group feeds
 */
class FileTest extends FileTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'file';

  /**
   * The FeedsTarget plugin being tested.
   *
   * @var \Drupal\feeds\Feeds\Target\File
   */
  protected $targetPlugin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Made-up entity type that we are referencing to.
    $referenceable_entity_type = $this->prophesize(EntityTypeInterface::class);
    $referenceable_entity_type->getKey('label')->willReturn('file label');
    $this->entityTypeManager->getDefinition('file')->willReturn($referenceable_entity_type)->shouldBeCalled();

    $this->targetPlugin = $this->instantiatePlugin();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return File::class;
  }

  /**
   * @covers ::prepareValue
   * @dataProvider dataProviderPrepareValue
   */
  public function testPrepareValue($expected, array $values, $expected_exception = NULL) {
    $method = $this->getProtectedClosure($this->targetPlugin, 'prepareValue');

    if ($expected_exception) {
      $this->expectException($expected_exception);
    }

    $method(0, $values);
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $values[$key]);
    }
  }

  /**
   * Data provider for testPrepareValue().
   */
  public function dataProviderPrepareValue() {
    return [
      // Description.
      [
        'expected' => [
          'description' => 'mydescription',
          'display' => FALSE,
        ],
        'values' => [
          'description' => 'mydescription',
        ],
      ],

      // Empty file target value.
      [
        'expected' => [],
        'values' => [
          'target_id' => '',
        ],
        'expected_exception' => EmptyFeedException::class,
      ],
    ];
  }

}
