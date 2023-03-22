<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests that feed type declares dependencies on fields used as target.
 *
 * - The field dependencies must be listed in the feed type config file.
 * - When deleting a field, the feed type must be updated.
 *
 * @group feeds
 */
class DependencyTest extends FeedsKernelTestBase {

  /**
   * Tests dependency on a single field.
   */
  public function testFieldDependency() {
    // Add a field to the article content type.
    $this->createFieldWithStorage('field_alpha');

    // Create a feed type that maps to that field.
    $feed_type = $this->createFeedType([
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'title'],
        ],
      ]),
    ]);

    // Assert that the field is listed as dependency.
    $dependencies = $feed_type->getDependencies();
    $expected = [
      'field.field.node.article.feeds_item',
      'field.field.node.article.field_alpha',
      'node.type.article',
    ];
    $this->assertEquals($expected, $dependencies['config']);

    // Now delete the field.
    FieldConfig::loadByName('node', 'article', 'field_alpha')
      ->delete();

    // Assert that the feed type mappings were updated.
    $feed_type = $this->reloadEntity($feed_type);
    $this->assertEquals($this->getDefaultMappings(), $feed_type->getMappings());
  }

  /**
   * Tests dependency on bundle.
   */
  public function testBundleDependency() {
    // Create a feed type that is creating nodes of type 'article'.
    $feed_type = $this->createFeedType();

    // Assert bundle dependency.
    $dependencies = $feed_type->getDependencies();
    $expected = [
      'field.field.node.article.feeds_item',
      'node.type.article',
    ];
    $this->assertEquals($expected, $dependencies['config']);

    // Delete the feed_item field first to avoid the error
    // "field_deleted_revision_xxx doesn't exist".
    FieldConfig::loadByName('node', 'article', 'feeds_item')
      ->delete();

    // Now delete the bundle.
    $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->load('article')
      ->delete();

    // Assert that the feed type no longer exists.
    $feed_type = $this->reloadEntity($feed_type);
    $this->assertNull($feed_type);
  }

}
