<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the feature of save a new revision.
 *
 * @group feeds
 */
class RevisionableEntityTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpBodyField();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpNodeType() {
    // Create a content type.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      // Ensure that this node type is revisionable.
      'new_revision' => TRUE,
    ]);
    $this->nodeType->save();
  }

  /**
   * Tests the revision toggle configuration.
   *
   * @dataProvider provideToggleRevision
   */
  public function testRevisionToggle($is_enabled) {
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'authorize' => FALSE,
        // Using the data provider, enable or disable the revision accordingly.
        'revision' => $is_enabled,
        'values' => [
          'type' => 'article',
        ],
      ],
      'custom_sources' => [
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
        'body' => [
          'label' => 'body',
          'value' => 'body',
          'machine_name' => 'body',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
          'settings' => [
            'language' => NULL,
          ],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'body', 'summary' => ''],
          'settings' => ['format' => 'plain_text'],
        ],
      ],
    ]);

    // Import first feed to create the first revision.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    $node = Node::load(1);
    $first_revision_id = $node->getRevisionId();

    // Now import updated feed to trigger a second revision to be created.
    $feed->setSource($this->resourcesPath() . '/csv/content_updated.csv');
    $feed->save();
    $feed->import();

    $node = Node::load(1);
    $second_revision_id = $node->getRevisionId();

    if ($is_enabled) {
      // When revision is enabled the first revision should not be the same as
      // the second one.
      $this->assertNotSame($first_revision_id, $second_revision_id);
    }
    else {
      // When revision is disabled the first revision should be the same as
      // second one.
      $this->assertSame($first_revision_id, $second_revision_id);
    }
  }

  /**
   * Data provider for ::testRevisionToggle().
   */
  public function provideToggleRevision() {
    return [
      'enable revision' => [TRUE],
      'disable revision' => [FALSE],
    ];
  }

  /**
   * Tests importing new revisions with mapping to revision fields.
   */
  public function testWithMappingToRevisionFields() {
    // Create a user with ID 42.
    $this->createUser([
      'uid' => 42,
    ]);

    // Create a feed type, map to body field. Set it to update existing items
    // and enable revision setting.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'created' => 'created',
      'alpha' => 'alpha',
      'beta' => 'beta',
      'body' => 'body',
    ], [
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'authorize' => FALSE,
        'revision' => TRUE,
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'body',
          'map' => ['value' => 'body'],
          'settings' => ['format' => 'plain_text'],
        ],
      ]),
    ]);

    // Import first feed to create the first revision.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Now map to certain fields related to the revision.
    $feed_type->addMapping([
      'target' => 'revision_timestamp',
      'map' => ['value' => 'created'],
    ])->addMapping([
      'target' => 'revision_log',
      'map' => ['value' => 'alpha'],
    ])->addMapping([
      'target' => 'revision_uid',
      'map' => ['target_id' => 'beta'],
      'settings' => [
        'reference_by' => 'uid',
        'autocreate' => FALSE,
      ],
    ])->save();

    // Reload feed and import again. Mapping has changed, so an update should
    // happen.
    $feed = $this->reloadEntity($feed);
    $feed->import();

    // Assert values of revision 2.
    $node = Node::load(1);
    $this->assertEquals(3, $node->getRevisionId());
    $this->assertEquals(1251936720, $node->revision_timestamp->value);
    $this->assertEquals('Lorem', $node->revision_log->value);
    $this->assertEquals(42, $node->revision_uid->target_id);

    // And assert values of revision 1.
    $revision1 = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadRevision(1);
    $this->assertNotEquals(1251936720, $revision1->revision_timestamp->value);
    $this->assertEquals('', $revision1->revision_log->value);
    $this->assertEquals(0, $revision1->revision_uid->target_id);
  }

}
