<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\DateRange;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\DateRange
 * @group feeds
 */
class DateRangeTest extends FieldTargetWithContainerTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'daterange';

  /**
   * The mocked feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The target definition.
   *
   * @var \Drupal\feeds\TargetDefinitionInterface
   */
  protected $targetDefinition;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->feedType = $this->createMock('Drupal\feeds\FeedTypeInterface');
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateRange', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'date']));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return DateRange::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin(array $configuration = []): TargetInterface {
    $configuration += [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
    ];
    return new DateRange($configuration, static::$pluginId, []);
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = [
      'value' => 1411606273,
      'end_value' => 1489582776,
    ];
    $method(0, $values);
    $this->assertSame(date(DateTimeItemInterface::DATE_STORAGE_FORMAT, 1411606273), $values['value']);
    $this->assertSame(date(DateTimeItemInterface::DATE_STORAGE_FORMAT, 1489582776), $values['end_value']);
  }

}
