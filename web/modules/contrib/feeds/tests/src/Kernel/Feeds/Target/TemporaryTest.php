<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Temporary
 * @group feeds
 */
class TemporaryTest extends FeedsKernelTestBase {

  /**
   * The feed type to test the temporary target with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and configure feed type. Map once to the temporary target.
    $this->feedType = $this->createFeedTypeForCsv([
      'title' => 'title',
      'alpha' => 'alpha',
      'beta' => 'beta',
    ], [
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'temporary_target',
          'map' => ['value' => 'alpha'],
        ],
      ],
    ]);
  }

  /**
   * Basic test import with a temporary target.
   */
  public function testOneTemporaryTarget() {
    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);
  }

  /**
   * Basic test import with two temporary targets.
   */
  public function testTwoTemporaryTarget() {
    // Set the mapping to add two temporary targets.
    $this->feedType->setMappings(
      [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'temporary_target',
          'map' => ['value' => 'alpha'],
        ],
        [
          'target' => 'temporary_target',
          'map' => ['value' => 'beta'],
        ],
      ]
    );
    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);
  }

}
