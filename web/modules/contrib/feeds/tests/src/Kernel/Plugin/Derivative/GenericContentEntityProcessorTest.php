<?php

namespace Drupal\Tests\feeds\Kernel\Plugin\Derivative;

use Drupal\feeds\Plugin\Derivative\GenericContentEntityProcessor as GenericContentEntityProcessorDerivative;
use Drupal\feeds\Feeds\Processor\GenericContentEntityProcessor;
use Drupal\feeds_test_plugin\Feeds\Processor\EntityTestProcessor;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * Tests the generic entity processor deriver.
 *
 * @group feeds
 */
class GenericContentEntityProcessorTest extends FeedsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'feeds', 'entity_test'];

  /**
   * Tests if the generic content entity processor can be overridden.
   */
  public function testOverridability() {
    // First, assert that the processor class for the entity_test entity type is
    // derived from the generic entity processor.
    $definitions = \Drupal::service('plugin.manager.feeds.processor')->getDefinitions();
    $this->assertEquals(GenericContentEntityProcessor::class, $definitions['entity:entity_test']['class']);
    $this->assertEquals(GenericContentEntityProcessorDerivative::class, $definitions['entity:entity_test']['deriver']);

    // Now enable the feeds_test_plugin module, which contains a specific
    // processor for the entity_test entity type.
    $this->installModule('feeds_test_plugin');
    $definitions = \Drupal::service('plugin.manager.feeds.processor')->getDefinitions();
    $this->assertEquals(EntityTestProcessor::class, $definitions['entity:entity_test']['class']);
    // Assert that this plugin does not use a deriver.
    $this->assertArrayNotHasKey('deriver', $definitions['entity:entity_test']);
  }

}
