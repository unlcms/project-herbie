<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Path
 * @group feeds
 */
class PathTest extends FeedsKernelTestBase {

  use PathautoTestHelperTrait;

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'node',
    'feeds',
    'path',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Install config for path module.
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('node');

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'title' => 'title',
      'alias' => 'alias',
    ], [
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'path',
          'map' => ['alias' => 'alias'],
        ],
      ],
    ]);

    $this->feedType->save();
  }

  /**
   * Tests importing paths.
   */
  public function testImportPaths() {
    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    $expected = [
      1 => '/lorem-ipsum-dolor',
      2 => '/ut-wisi-enim',
    ];

    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->path->alias);
    }
  }

  /**
   * Tests updating paths.
   */
  public function testUpdateNodePaths() {
    // Create a node with an alias.
    $node = Node::create([
      'title' => 'Lorem ipsum',
      'type' => 'article',
      'path' => ['alias' => 'lorie', 'pathauto' => 0],
    ]);
    $node->save();
    $this->assertEquals('lorie', $node->path->alias);

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    $node = $this->reloadEntity($node);
    $this->assertEquals('/lorem-ipsum-dolor', $node->path->alias);
  }

  /**
   * Tests importing paths when pathauto is enabled.
   */
  public function testImportPathsWithPathauto() {
    $this->installPathauto();
    $this->testImportPaths();
  }

  /**
   * Tests importing paths when pathauto is enabled.
   */
  public function testUpdateNodePathsWithPathauto() {
    $this->installPathauto();
    $this->testUpdateNodePaths();
  }

  /**
   * Tests importing with and without automatic aliases.
   */
  public function testImportPathauto() {
    $this->installPathauto();

    // Create a feed type with mapping to pathauto.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'alias' => 'alias',
      'epsilon' => 'epsilon',
    ]);

    $feed_type->addMapping([
      'target' => 'path',
      'map' => [
        'alias' => 'alias',
        'pathauto' => 'epsilon',
      ],
    ]);
    $feed_type->save();

    // Import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // The first item has automatic alias enabled. The second one has not.
    $expected = [
      1 => '/content/lorem-ipsum',
      2 => '/ut-wisi-enim',
    ];

    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->path->alias);
    }
  }

  /**
   * Installs pathauto and configures a pattern for nodes.
   */
  protected function installPathauto() {
    $this->installModule('ctools');
    $this->installModule('token');
    $this->installModule('pathauto');
    $this->installConfig(['pathauto', 'system', 'node']);

    // Create pattern for nodes.
    $this->createPattern('node', '/content/[node:title]');

    \Drupal::service('router.builder')->rebuild();
  }

}
