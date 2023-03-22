<?php

namespace Drupal\Tests\feeds_log\Kernel\Entity;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\Tests\feeds_log\Kernel\FeedsLogKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds_log\Entity\ImportLog
 *
 * @group feeds_log
 */
class ImportLogTest extends FeedsLogKernelTestBase {

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed to test with.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The import log to test with.
   *
   * @var \Drupal\feeds_log\Entity\ImportLog
   */
  protected $importLog;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a feed type and a feed.
    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
    ]);
    $this->feed = $this->createFeed($this->feedType->id(), [
      'title' => 'My feed',
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Create log entry.
    $this->importLog = $this->container->get('entity_type.manager')
      ->getStorage('feeds_import_log')
      ->generate($this->feed);
    $this->importLog->save();
  }

  /**
   * @covers ::logSource
   */
  public function testLogSource() {
    $fetcher_result = $this->prophesize(FetcherResultInterface::class);
    $fetcher_result->getFilePath()->willReturn('foo.txt');
    $fetcher_result->getRaw()->willReturn('bar');

    $this->assertEquals('public://feeds/log/1/source/foo.txt', $this->importLog->logSource($fetcher_result->reveal()));
    $this->assertEquals('bar', file_get_contents('public://feeds/log/1/source/foo.txt'));
  }

  /**
   * @covers ::logItem
   */
  public function testLogItem() {
    $item_data = ['a' => 'foo', 'b' => 'bar'];
    $item = $this->prophesize(ItemInterface::class);
    $item->toArray()->willReturn($item_data);

    $this->assertEquals('public://feeds/log/1/items/0.json', $this->importLog->logItem($item->reveal()));
    $this->assertEquals(json_encode($item_data), file_get_contents('public://feeds/log/1/items/0.json'));
  }

  /**
   * @covers ::addLogEntry
   */
  public function testAddLogEntry() {
    $entry = [];
    $this->assertEquals(1, $this->importLog->addLogEntry($entry));
    $this->assertEquals(1, $entry['import_id']);
    $this->assertEquals(1, $entry['feed_id']);
  }

  /**
   * @covers ::addLogEntry
   */
  public function testAddLogEntryWithEmptyData() {
    $this->assertEquals(1, $this->importLog->addLogEntry());
  }

  /**
   * @covers ::updateLogEntry
   */
  public function testUpdateLogEntry() {
    $this->importLog->addLogEntry();
    $entry['lid'] = 1;
    $this->assertSame(1, $this->importLog->updateLogEntry($entry));
  }

  /**
   * @covers ::getFeedLabel
   */
  public function testGetFeedLabel() {
    $this->assertEquals('My feed', $this->importLog->getFeedLabel());
  }

  /**
   * @covers ::getImportStartTime
   */
  public function testGetImportStartTime() {
    $start_time = $this->importLog->getImportStartTime();
    $this->assertNotEmpty($start_time);
    $this->assertIsInt($start_time);
  }

  /**
   * @covers ::getImportFinishTime
   */
  public function testGetImportFinishTime() {
    $time = time();
    $this->importLog->end->value = $time;
    $this->assertEquals($time, $this->importLog->getImportFinishTime());
  }

  /**
   * @covers ::getImportFinishTime
   */
  public function testGetImportFinishTimeWhenEmpty() {
    $this->assertEmpty($this->importLog->getImportFinishTime());
  }

  /**
   * @covers ::getSources
   */
  public function testGetSourcesWithSingleSource() {
    // Log a single source.
    $fetcher_result = $this->prophesize(FetcherResultInterface::class);
    $fetcher_result->getFilePath()->willReturn('foo.txt');
    $fetcher_result->getRaw()->willReturn('bar');
    $this->importLog->logSource($fetcher_result->reveal());

    $expected = ['public://feeds/log/1/source/foo.txt'];
    $this->assertEquals($expected, $this->importLog->getSources());
  }

  /**
   * @covers ::getSources
   */
  public function testGetSourcesWithMultipleSources() {
    // Log multiple sources.
    $filenames = [
      'foo.txt',
      'bar.txt',
      'qux.txt',
    ];
    foreach ($filenames as $filename) {
      $fetcher_result = $this->prophesize(FetcherResultInterface::class);
      $fetcher_result->getFilePath()->willReturn($filename);
      $fetcher_result->getRaw()->willReturn('bar');
      $this->importLog->logSource($fetcher_result->reveal());
    }

    $expected = [
      'public://feeds/log/1/source/foo.txt',
      'public://feeds/log/1/source/bar.txt',
      'public://feeds/log/1/source/qux.txt',
    ];
    $this->assertEquals($expected, $this->importLog->getSources());
  }

  /**
   * @covers ::getQuery
   */
  public function testGetQuery() {
    $this->assertInstanceOf(SelectInterface::class, $this->importLog->getQuery());
  }

  /**
   * @covers ::getLogEntries
   */
  public function testGetLogEntries() {
    // Add two log entries.
    $this->importLog->addLogEntry();
    $this->importLog->addLogEntry();

    $expected_defaults = [
      'entity_id' => '0',
      'entity_label' => '',
      'entity_type_id' => '',
      'item' => '',
      'item_id' => '',
      'operation' => '',
      'message' => '',
      'variables' => [],
      'timestamp' => '0',
    ];

    // Define the expected values for each log entry that was created.
    $expected[0] = (object) $expected_defaults;
    $expected[1] = (object) $expected_defaults;
    $this->assertEquals($expected, $this->importLog->getLogEntries());
  }

}
