<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Base class for testing feeds field targets.
 */
abstract class FieldTargetTestBase extends FeedsUnitTestCase {

  /**
   * Returns the target class.
   *
   * @return string
   *   Returns the full class name of the target to test.
   */
  abstract protected function getTargetClass();

  /**
   * @covers ::prepareTarget
   */
  public function testPrepareTarget() {
    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    $this->assertInstanceof(FieldTargetDefinition::class, $method($this->getMockFieldDefinition()));
  }

  /**
   * Returns available properties for the current target plugin.
   *
   * @return string[]
   *   A list of property names.
   */
  protected function getTargetProperties(): array {
    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    return $method($this->getMockFieldDefinition())
      ->getProperties();
  }

  /**
   * Instantiates the target plugin to test.
   *
   * @param array $configuration
   *   (optional) The configuration to pass to the plugin.
   *
   * @return \Drupal\feeds\Plugin\Type\Target\TargetInterface
   *   A FeedsTarget plugin instance.
   */
  protected function instantiatePlugin(array $configuration = []): TargetInterface {
    $target_class = $this->getTargetClass();
    $method = $this->getMethod($target_class, 'prepareTarget')->getClosure();

    $configuration += [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];
    return new $target_class($configuration, static::$pluginId, []);
  }

  /**
   * This test covers if all target plugins can be instantiated.
   */
  public function testInstantiatePlugin() {
    $this->assertInstanceof($this->getTargetClass(), $this->instantiatePlugin());
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValueWithNullValue() {
    set_error_handler([$this, 'handleError'], E_DEPRECATED);

    $values = [];
    foreach ($this->getTargetProperties() as $key) {
      $values[$key] = NULL;
    }

    try {
      $target = $this->instantiatePlugin();
      $method = $this->getProtectedClosure($target, 'prepareValue');
      $method(0, $values);
    }
    catch (EmptyFeedException $e) {
      // Plugins may throw an EmptyFeedException when NULL is passed.
    }
    catch (\ErrorException $e) {
      $this->fail($e->getMessage());
    }
    finally {
      restore_error_handler();
    }
    $this->assertIsArray($values);
  }

  /**
   * Make sure that PHP deprecations are handled as an error.
   */
  public function handleError(int $errno, string $errstr) {
    throw new \ErrorException($errstr);
  }

}
