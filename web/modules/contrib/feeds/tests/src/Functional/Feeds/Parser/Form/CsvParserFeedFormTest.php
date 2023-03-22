<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Parser\Form;

use Drupal\feeds\Entity\Feed;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\Form\CsvParserFeedForm
 * @group feeds
 */
class CsvParserFeedFormTest extends FeedsBrowserTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ], [
      'fetcher' => 'upload',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
        ],
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);
  }

  /**
   * Tests importing a feed using the default settings.
   */
  public function testImportSingleFile() {
    // Create feed and import.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[plugin_fetcher_source]' => \Drupal::service('file_system')->realpath($this->resourcesPath() . '/csv/nodes_comma.csv'),
    ];
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->submitForm($edit, t('Save and import'));

    // Load feed.
    $feed = Feed::load(1);

    // Assert that 2 nodes have been created.
    static::assertEquals(9, $feed->getItemCount());
    $this->assertNodeCount(9);
  }

  /**
   * Tests importing a feed using various delimiters.
   *
   * @param string $delimiter
   *   The delimiter to test.
   * @param string $csv_file
   *   The file to import.
   *
   * @dataProvider delimiterDataProvider
   */
  public function testDelimiterSetting($delimiter, $csv_file) {
    // Create feed and import.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[plugin_fetcher_source]' => \Drupal::service('file_system')->realpath($this->resourcesPath() . '/csv/' . $csv_file),
      'plugin[parser][delimiter]' => $delimiter,
    ];
    $this->drupalGet('feed/add/' . $this->feedType->id());
    $this->submitForm($edit, t('Save and import'));

    // Load feed.
    $feed = Feed::load(1);

    // Assert that 9 nodes have been created.
    static::assertEquals(9, $feed->getItemCount());
    $this->assertNodeCount(9);
  }

  /**
   * Data provider for ::testDelimiterSetting().
   */
  public function delimiterDataProvider() {
    return [
      'comma' => [',', 'nodes_comma.csv'],
      'semicolon' => [';', 'nodes_semicolon.csv'],
      'tab' => ['TAB', 'nodes_tab.csv'],
      'pipe' => ['|', 'nodes_pipe.csv'],
      'plus' => ['+', 'nodes_plus.csv'],
    ];
  }

}
