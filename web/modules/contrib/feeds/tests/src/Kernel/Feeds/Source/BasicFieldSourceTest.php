<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Source;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Source\BasicFieldSource
 * @group feeds
 */
class BasicFieldSourceTest extends FeedsKernelTestBase {

  use TaxonomyTestTrait;

  /**
   * Tests importing using a text field source.
   */
  public function testImportWithTextfieldSource() {
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
      ],
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
          'unique' => ['guid' => TRUE],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'parent:alpha'],
        ],
      ],
    ]);

    // Add a field to this feed type.
    $this->createFieldWithStorage('alpha', [
      'entity_type' => 'feeds_feed',
      'bundle' => $feed_type->id(),
    ]);

    // Import a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'alpha' => [
        0 => [
          'value' => 'Dolor Sit Amet',
        ],
      ],
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that two nodes were imported with the title 'Dolor Sit Amet'.
    $node1 = Node::load(1);
    $this->assertEquals('Dolor Sit Amet', $node1->getTitle());
    $node2 = Node::load(2);
    $this->assertEquals('Dolor Sit Amet', $node2->getTitle());
  }

  /**
   * Tests importing using a taxonomy term reference source.
   */
  public function testImportWithTaxonomyTermReferenceSource() {
    // Install taxonomy module, add field to article.
    $vocabulary = $this->setUpTermReferenceField();

    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_tags',
          'map' => ['target_id' => 'parent:field_tags'],
          'settings' => [
            'reference_by' => 'tid',
          ],
        ],
      ]),
    ]);

    // Add a term reference field for the feed type.
    $this->createFieldWithStorage('field_tags', [
      'entity_type' => 'feeds_feed',
      'bundle' => $feed_type->id(),
      'type' => 'entity_reference',
      'storage' => [
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
      ],
      'field' => [
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [
            // Restrict selection of terms to a single vocabulary.
            'target_bundles' => [
              $vocabulary->id() => $vocabulary->id(),
            ],
          ],
        ],
      ],
    ]);

    // Create a term.
    $term1 = $this->createTerm($vocabulary);
    $term2 = $this->createTerm($vocabulary);

    // Import a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'field_tags' => [
        0 => [
          'target_id' => $term2->id(),
        ],
      ],
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that two nodes were imported with the term from the feed.
    $node1 = Node::load(1);
    $this->assertEquals($term2->id(), $node1->field_tags->target_id);
    $node2 = Node::load(2);
    $this->assertEquals($term2->id(), $node2->field_tags->target_id);
  }

}
