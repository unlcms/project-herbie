<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Target;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\File
 * @group feeds
 */
class FileTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'feeds_test_files',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a file field.
    $this->createFieldWithStorage('field_file', [
      'type' => 'file',
      'field' => [
        'settings' => ['file_extensions' => 'png, gif, jpg, jpeg'],
      ],
    ]);
  }

  /**
   * Tests importing several files.
   */
  public function test() {
    // Create a feed type for importing nodes with files.
    $feed_type = $this->createFeedTypeForCsv([
      'title' => 'title',
      'timestamp' => 'timestamp',
      'file' => 'file',
    ], [
      'fetcher' => 'http',
      'fetcher_configuration' => [],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'field_file',
          'map' => ['target_id' => 'file'],
          'settings' => [
            'reference_by' => 'filename',
            'existing' => '2',
            'autocreate' => FALSE,
          ],
        ],
      ],
    ]);

    // Create a feed and import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => \Drupal::request()->getSchemeAndHttpHost() . '/testing/feeds/files.csv',
    ]);
    $feed->import();

    // Assert that all files were imported.
    foreach ($this->getListOfTestFiles() as $file) {
      $file_path = $this->container->get('file_system')->realpath('public://' . date('Y-m') . '/' . $file);
      $this->assertFileExists($file_path);
    }
  }

  /**
   * Lists test files.
   */
  protected function getListOfTestFiles() {
    return [
      'tubing.jpeg',
      'foosball.jpeg',
      'attersee.jpeg',
      'hstreet.jpeg',
      'la fayette.jpeg',
      'attersee.JPG',
    ];
  }

}
