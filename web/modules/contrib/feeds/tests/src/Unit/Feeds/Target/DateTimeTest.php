<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\feeds\Feeds\Target\DateTime;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\DateTime
 * @group feeds
 */
class DateTimeTest extends FieldTargetWithContainerTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'datetime';

  /**
   * The feed type entity.
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
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateTime', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'time']));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return DateTime::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin(array $configuration = []): TargetInterface {
    $configuration += [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
    ];
    return new DateTime($configuration, static::$pluginId, []);
  }

  /**
   * Tests preparing a value that succeeds.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateTime', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'date']));

    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 1411606273];
    $method(0, $values);
    $this->assertSame(date(DateTimeItemInterface::DATE_STORAGE_FORMAT, 1411606273), $values['value']);
  }

  /**
   * Tests preparing a value that fails.
   *
   * @covers ::prepareValue
   */
  public function testWithErrors() {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => '2000-05-32'];
    $method(0, $values);
    $this->assertSame('', $values['value']);
  }

  /**
   * Tests parsing a year value.
   *
   * @covers ::prepareValue
   */
  public function testYearValue() {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => '2000'];
    $method(0, $values);
    $this->assertSame('2000-01-01T00:00:00', $values['value']);
  }

  /**
   * Test the timezone configuration.
   */
  public function testGetTimezoneConfiguration() {
    // Timezone setting for default timezone.
    $container = new ContainerBuilder();
    $config = ['system.date' => ['timezone.default' => 'UTC']];
    $container->set('config.factory', $this->getConfigFactoryStub($config));
    \Drupal::setContainer($container);

    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateTime', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'date']));

    // Test timezone options with one of the timezones.
    $configuration = [
      'timezone' => 'Europe/Helsinki',
    ];

    $target = $this->instantiatePlugin($configuration);
    $method = $this->getProtectedClosure($target, 'getTimezoneConfiguration');

    $this->assertSame('Europe/Helsinki', $method());

    // Test timezone options with site default option.
    $configuration = [
      'timezone' => '__SITE__',
    ];
    $target = $this->instantiatePlugin($configuration);
    $method = $this->getProtectedClosure($target, 'getTimezoneConfiguration');

    $this->assertSame('UTC', $method());
  }

}
