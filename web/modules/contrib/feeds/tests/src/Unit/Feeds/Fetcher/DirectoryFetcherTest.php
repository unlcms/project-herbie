<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Feeds\Fetcher\DirectoryFetcher;
use Drupal\feeds\State;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\DirectoryFetcher
 * @group feeds
 */
class DirectoryFetcherTest extends FeedsUnitTestCase {

  /**
   * The Feeds fetcher plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Fetcher\DirectoryFetcher
   */
  protected $fetcher;

  /**
   * The state object.
   *
   * @var \Drupal\feeds\StateInterface
   */
  protected $state;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $feed_type = $this->createMock('Drupal\feeds\FeedTypeInterface');
    $container = new ContainerBuilder();
    $container->set('stream_wrapper_manager', $this->getMockStreamWrapperManager());
    $this->fetcher = new DirectoryFetcher(['feed_type' => $feed_type], 'directory', []);
    $this->fetcher->setStringTranslation($this->getStringTranslationStub());

    $this->state = new State();

    $this->feed = $this->createMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds'));

    // Prepare filesystem.
    touch('vfs://feeds/test_file_1.txt');
    touch('vfs://feeds/test_file_2.txt');
    touch('vfs://feeds/test_file_3.txt');
    touch('vfs://feeds/test_file_3.mp3');
    chmod('vfs://feeds/test_file_3.txt', 0333);
    mkdir('vfs://feeds/subdir');
    touch('vfs://feeds/subdir/test_file_4.txt');
    touch('vfs://feeds/subdir/test_file_4.mp3');
  }

  /**
   * Tests fetching a file.
   *
   * @covers ::fetch
   */
  public function testFetchFile() {
    $feed = $this->createMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file_1.txt'));
    $result = $this->fetcher->fetch($feed, $this->state);
    $this->assertSame('vfs://feeds/test_file_1.txt', $result->getFilePath());
  }

  /**
   * Tests fetching from a directory on which we don't have read permissions.
   *
   * @covers ::fetch
   */
  public function testFetchDir() {
    $result = $this->fetcher->fetch($this->feed, $this->state);
    $this->assertSame($this->state->total, 2);
    $this->assertSame('vfs://feeds/test_file_1.txt', $result->getFilePath());
    $this->assertSame('vfs://feeds/test_file_2.txt', $this->fetcher->fetch($this->feed, $this->state)->getFilePath());

    chmod('vfs://feeds', 0333);
    $this->expectException(\RuntimeException::class);
    $result = $this->fetcher->fetch($this->feed, $this->state);
  }

  /**
   * Tests fetching a directory resursively.
   *
   * @covers ::fetch
   */
  public function testRecursiveFetchDir() {
    $this->fetcher->setConfiguration(['recursive_scan' => TRUE]);

    $result = $this->fetcher->fetch($this->feed, $this->state);
    $this->assertSame($this->state->total, 3);
    $this->assertSame('vfs://feeds/test_file_1.txt', $result->getFilePath());
    $this->assertSame('vfs://feeds/test_file_2.txt', $this->fetcher->fetch($this->feed, $this->state)->getFilePath());
    $this->assertSame('vfs://feeds/subdir/test_file_4.txt', $this->fetcher->fetch($this->feed, $this->state)->getFilePath());
  }

  /**
   * Tests fetching an empty directory.
   *
   * @covers ::fetch
   */
  public function testEmptyDirectory() {
    mkdir('vfs://feeds/emptydir');
    $feed = $this->createMock('Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/emptydir'));

    $this->expectException(EmptyFeedException::class);
    $result = $this->fetcher->fetch($feed, $this->state);
  }

}
