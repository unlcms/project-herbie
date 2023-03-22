<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests for the entityreference target.
 *
 * @group feeds
 */
class EntityReferenceTest extends FeedsKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Tests if items are updated that previously referenced a missing item.
   *
   * When importing a feed that references items that are imported by an other
   * feed later, the referenced items do not exist yet. In this case these items
   * should be updated on a second import, when the referenced items may exist
   * by then.
   *
   * In this test, feed types for two content types are created: one for the
   * article content type and one for the page content type. The content type
   * 'page' has a field called 'field_article' that references article nodes.
   * Content for the 'page' content type is imported first, which means that the
   * articles that the source references, do not exist yet. Articles are
   * imported next. Finally, the source for the 'page' content type is imported
   * again to ensure that references to the article nodes do get imported after
   * all, even though the source did not change.
   *
   * Feeds usually skips importing a source item if it did not change since the
   * previous import, but in case of previously missing references, it should do
   * not.
   */
  public function testUpdatingMissingReferences() {
    // Create a content type.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $type->save();
    // Add an entityreference field to this content type.
    $this->createEntityReferenceField('node', 'page', 'field_article', 'Article', 'node', 'default', [
      'target_bundles' => ['article'],
    ]);

    // Create feed type for the article content type.
    $this->createFeedType([
      'id' => 'article_feed_type',
      'label' => 'Article importer',
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
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
    ]);

    // Create feed type for the 'page' content type, with a mapping to the
    // entityreference field 'field_article'.
    $this->createFeedType([
      'id' => 'page_feed_type',
      'label' => 'Page importer',
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'page',
        ],
      ],
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
        'article' => [
          'label' => 'article',
          'value' => 'article',
          'machine_name' => 'article',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_article',
          'map' => ['target_id' => 'article'],
          'settings' => [
            'reference_by' => 'feeds_item',
            'feeds_item' => 'guid',
            'autocreate' => 0,
          ],
        ],
      ]),
    ]);

    // Import pages.
    $feed = $this->createFeed('page_feed_type', [
      'source' => $this->resourcesPath() . '/csv/content-with-reference.csv',
    ]);
    $feed->import();

    // Assert two created nodes.
    $this->assertNodeCount(2);
    $node = Node::load(1);
    // Assert that field_article is empty at the moment.
    $this->assertEquals([], $node->field_article->getValue());

    // Import second feed.
    $feed2 = $this->createFeed('article_feed_type', [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed2->import();
    $this->assertNodeCount(4);

    // And re-import first feed.
    $feed->import();

    // Reload node.
    $node = $this->reloadEntity($node);
    $this->assertEquals(4, $node->field_article->target_id);
    // Check node 2 too.
    $node2 = Node::load(2);
    $this->assertEquals(3, $node2->field_article->target_id);

    // Ensure that the nodes aren't updated again. Change the titles of all page
    // nodes, so we can check that these won't be updated by Feeds.
    $node->title->value = 'Page 1';
    $node->save();
    $node2->title->value = 'Page 2';
    $node2->save();

    // And re-import first feed again.
    $feed->import();

    // Ensure that the nodes were not updated.
    $node = $this->reloadEntity($node);
    $this->assertEquals('Page 1', $node->title->value);
    $node2 = $this->reloadEntity($node2);
    $this->assertEquals('Page 2', $node2->title->value);
  }

  /**
   * Tests if articles get an author later.
   *
   * If articles are imported before their authors, the articles won't have an
   * author yet on the first import. When the articles get imported again after
   * the authors are imported, the articles should get an author after all.
   */
  public function testUpdatingMissingNodeAuthors() {
    // Create feed type for importing articles.
    $this->createFeedType([
      'id' => 'article_feed_type',
      'label' => 'Article importer',
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
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
        'author' => [
          'label' => 'author',
          'value' => 'author',
          'machine_name' => 'author',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'uid',
          'map' => ['target_id' => 'author'],
          'settings' => [
            'reference_by' => 'name',
            'autocreate' => 0,
          ],
        ],
      ]),
    ]);

    // Create feed type for users.
    $this->createFeedType([
      'id' => 'authors',
      'label' => 'Authors',
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor' => 'entity:user',
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [],
      ],
      'custom_sources' => [
        'name' => [
          'label' => 'name',
          'value' => 'name',
          'machine_name' => 'name',
        ],
        'mail' => [
          'label' => 'mail',
          'value' => 'mail',
          'machine_name' => 'mail',
        ],
        'status' => [
          'label' => 'status',
          'value' => 'status',
          'machine_name' => 'status',
        ],
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'name'],
          'unique' => [
            'value' => 1,
          ],
        ],
        [
          'target' => 'mail',
          'map' => ['value' => 'mail'],
        ],
        [
          'target' => 'status',
          'map' => ['value' => 'status'],
        ],
      ],
    ]);

    // Import articles.
    $article_feed = $this->createFeed('article_feed_type', [
      'source' => $this->resourcesPath() . '/csv/content-with-author.csv',
    ]);
    $article_feed->import();

    // Assert three created nodes.
    $this->assertNodeCount(3);
    $node = Node::load(1);
    // Assert that the first node doesn't currently have an author.
    $this->assertEquals(0, $node->uid->target_id);

    // Import authors.
    $author_feed = $this->createFeed('authors', [
      'source' => $this->resourcesPath() . '/csv/users.csv',
    ]);
    $author_feed->import();

    // And re-import first feed. Previously imported articles now should get an
    // author.
    $article_feed->import();

    // Reload node 1 and check if it got an author now.
    $nodes[1] = $this->reloadEntity($node);
    $this->assertEquals(1, $nodes[1]->uid->target_id);
    // Check nodes 2 and 3 too.
    $nodes[2] = Node::load(2);
    $this->assertEquals(1, $nodes[2]->uid->target_id);
    $nodes[3] = Node::load(3);
    $this->assertEquals(2, $nodes[3]->uid->target_id);

    // Ensure that the nodes aren't updated again. Change the titles of all
    // articles, so we can check that these won't be updated by Feeds.
    for ($i = 1; $i <= 3; $i++) {
      $nodes[$i]->title->value = 'Article ' . $i;
      $nodes[$i]->save();
    }

    // And re-import first feed again. No nodes should get updated.
    $article_feed->import();

    // Ensure that the nodes were not updated.
    for ($i = 1; $i <= 3; $i++) {
      $node = $this->reloadEntity($nodes[$i]);
      $this->assertEquals('Article ' . $i, $node->title->value);
    }
  }

  /**
   * Tests if terms get their parent on a second import.
   *
   * If parent terms appear later in the feed, earlier imported terms won't get
   * that parent. This test ensures that these terms get the parent after all on
   * a second import.
   */
  public function testUpdatingMissingParentTerms() {
    $vocabulary = $this->installTaxonomyModuleWithVocabulary();

    // Create feed type for terms.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor' => 'entity:taxonomy_term',
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'vid' => 'tags',
        ],
      ],
      'custom_sources' => [
        'name' => [
          'label' => 'name',
          'value' => 'name',
          'machine_name' => 'name',
        ],
        'parent' => [
          'label' => 'parent',
          'value' => 'parent',
          'machine_name' => 'parent',
        ],
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'name'],
          'unique' => [
            'value' => 1,
          ],
        ],
        [
          'target' => 'description',
          'map' => ['value' => 'name'],
          'settings' => [
            ['format' => 'plain_text'],
          ],
        ],
        [
          'target' => 'parent',
          'map' => ['target_id' => 'parent'],
          'settings' => [
            'reference_by' => 'name',
            'autocreate' => 0,
          ],
        ],
      ],
    ]);

    // First import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/terms-with-parent-later-in-file.csv',
    ]);
    $feed->import();

    // Assert that all terms got imported.
    $terms = Term::loadMultiple();
    $expected_term_names = [
      1 => 'Belgium',
      2 => 'Europe',
      3 => 'Netherlands',
    ];
    foreach ($expected_term_names as $term_id => $expected_term_name) {
      // Check term name and description.
      $this->assertEquals($expected_term_name, $terms[$term_id]->name->value);
      $this->assertEquals($expected_term_name, $terms[$term_id]->description->value);
    }

    // Assert that "Belgium" did not get a parent assigned, but "Netherlands"
    // did, since the latter appeared later in the file.
    $this->assertEquals([], $this->entityTypeManager->getStorage('taxonomy_term')->loadParents(1));
    $this->assertEquals([2], array_keys($this->entityTypeManager->getStorage('taxonomy_term')->loadParents(3)));

    // Second import. Now Belgium should have a parent term.
    $feed->import();
    $this->assertEquals([2], array_keys($this->entityTypeManager->getStorage('taxonomy_term')->loadParents(1)));

    // Ensure that terms aren't updated again. Change the descriptions of all
    // terms, so we can check that these won't be updated by Feeds.
    for ($i = 1; $i <= 3; $i++) {
      $terms[$i]->description->value = 'Description of term ' . $i;
      $terms[$i]->save();
    }

    // And re-import.
    $feed->import();

    // Ensure that the terms were not updated.
    for ($i = 1; $i <= 3; $i++) {
      $term = $this->reloadEntity($terms[$i]);
      $this->assertEquals('Description of term ' . $i, $term->description->value);
    }
  }

  /**
   * Tests if only a single entity is referenced per value.
   *
   * In case multiple entities exist for a source value mapped to an entity
   * reference field, ensure that by default only one entity is returned.
   */
  public function testWithSingleReference() {
    // Create a content type for which entities will be referenced.
    $type = NodeType::create([
      'type' => 'event',
      'name' => 'Event',
    ]);
    $type->save();
    // Add a text field on this type that will be used as the field to reference
    // by.
    $this->createFieldWithStorage('field_alpha', [
      'bundle' => 'event',
    ]);

    // Create two event nodes, both with the same value for the field "alpha".
    Node::create([
      'title' => 'Event 1',
      'type' => 'event',
      'field_alpha' => 'Lorem',
    ])->save();
    Node::create([
      'title' => 'Event 2',
      'type' => 'event',
      'field_alpha' => 'Lorem',
    ])->save();

    // Add an entity reference field to the content type "article", referencing
    // nodes of type "event" and accepting multiple values.
    $this->createEntityReferenceField('node', 'article', 'field_event', 'Event', 'node', 'default', [
      'target_bundles' => ['event'],
    ], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Create a feed type for importing articles, with a mapper to the
    // entityreference field 'field_event'.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'guid' => 'guid',
      'alpha' => 'alpha',
    ], [
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_event',
          'map' => ['target_id' => 'alpha'],
          'settings' => [
            'reference_by' => 'field_alpha',
          ],
        ],
      ]),
    ]);

    // Import articles.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that now four nodes in total exist.
    $this->assertNodeCount(4);

    // Assert that the first article references only one entity and the second
    // none.
    $expected_values_per_node = [
      3 => [
        ['target_id' => 1],
      ],
      4 => [],
    ];
    foreach ($expected_values_per_node as $nid => $expected_value) {
      $node = Node::load($nid);
      $this->assertEquals($expected_value, $node->field_event->getValue());
    }
  }

}
