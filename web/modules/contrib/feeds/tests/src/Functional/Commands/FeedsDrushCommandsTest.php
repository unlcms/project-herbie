<?php

namespace Drupal\Tests\feeds\Functional\Commands;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * @coversDefaultClass \Drupal\feeds\Commands\FeedsDrushCommands
 * @group feeds
 */
class FeedsDrushCommandsTest extends FeedsBrowserTestBase {

  use DrushTestTrait {
    getSimplifiedErrorOutput as traitGetSimplifiedErrorOutput;
  }

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'rss2 atom',
      ],
    ]);
  }

  /**
   * @covers ::listFeeds
   */
  public function testListFeeds() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id());

    // Execute drush command to list feeds.
    $this->drush('feeds:list-feeds');

    $output = $this->getOutputAsList();

    // Assert columns get displayed.
    $expected_columns = [
      'Feed type',
      'Feed ID',
      'Title',
      'Last imported',
      'Next import',
      'Feed source',
      'Item count',
      'Status',
    ];
    foreach ($expected_columns as $column) {
      $this->assertStringContainsString($column, $output[1]);
    }

    // Assert that the feed gets displayed.
    // @todo assert display of dates.
    $values = [
      $this->feedType->id(),
      $feed->label(),
    ];
    foreach ($values as $value) {
      $this->assertStringContainsString($value, $output[3]);
    }
  }

  /**
   * @covers ::enableFeed
   */
  public function testEnableFeed() {
    // Create a feed that is not enabled.
    $feed = $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'status' => FALSE,
    ]);
    $this->assertFalse((bool) $feed->status->value);

    // Enable the feed using drush.
    $this->drush('feeds:enable', [$feed->id()]);

    // Assert that the feed is now enabled.
    $feed = $this->reloadEntity($feed);
    $this->assertTrue((bool) $feed->status->value);
    $this->assertStringContainsString('The feed "Foo" has been enabled.', $this->getErrorOutput());

    // Try to enable it again.
    $this->drush('feeds:enable', [$feed->id()]);
    $this->assertStringContainsString('This feed is already enabled.', $this->getErrorOutput());
  }

  /**
   * @covers ::disableFeed
   */
  public function testDisableFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
    ]);

    // Disable the feed using drush.
    $this->drush('feeds:disable', [$feed->id()]);

    // Assert that the feed is now disabled.
    $feed = $this->reloadEntity($feed);
    $this->assertFalse((bool) $feed->status->value);
    $this->assertStringContainsString('The feed "Foo" has been disabled.', $this->getErrorOutput());

    // Try to disable it again.
    $this->drush('feeds:disable', [$feed->id()]);
    $this->assertStringContainsString('This feed is already disabled.', $this->getErrorOutput());
  }

  /**
   * @covers ::importFeed
   */
  public function testImportFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);

    // Import feed using drush.
    $this->drush('feeds:import', [$feed->id()]);
    $this->assertStringContainsString('Created 25 Article items.', $this->getErrorOutput());

    $this->assertNodeCount(25);
    $node = Node::load(1);
    $this->assertEquals('Adaptivethemes: Why I killed Node, may it RIP', $node->title->value);
  }

  /**
   * Tests that importing a locked feed fails.
   */
  public function testImportFeedFailsWhenLocked() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);

    // Lock feed.
    $feed->lock();

    // Try importing a feed using drush.
    $this->drush('feeds:import', [$feed->id()]);

    // Assert that no nodes got imported.
    $this->assertNodeCount(0);

    // Assert the output.
    $this->assertStringContainsString('The feed became locked before the import could begin', $this->getSimplifiedErrorOutput());
  }

  /**
   * Tests importing a disabled feed.
   *
   * When the feed is disabled, the import should not happen unless
   * when passing the --import-disabled option.
   */
  public function testImportDisabledFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
      'status' => FALSE,
    ]);

    // Try importing feed using drush.
    $this->drush('feeds:import', [$feed->id()], [], NULL, NULL, 1);

    // Assert that no nodes got imported.
    $this->assertNodeCount(0);

    // Assert the output.
    $this->assertStringContainsString('The specified feed is disabled. If you want to force importing, specify --import-disabled.', $this->getSimplifiedErrorOutput());

    // Now try to import the feed with the --import-disabled option.
    $this->drush('feeds:import', [$feed->id()], ['import-disabled' => NULL]);

    // Assert that nodes got imported now.
    $this->assertNodeCount(25);
  }

  /**
   * Tests importing all feeds of all types.
   *
   * @covers ::importAllFeeds
   */
  public function testImportAllFeeds() {
    // Create three feeds.
    $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $this->createFeed($this->feedType->id(), [
      'title' => 'Bar',
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // This feed is disabled and should not be imported.
    $this->createFeed($this->feedType->id(), [
      'title' => 'Baz',
      'source' => $this->resourcesPath() . '/atom/entries.atom',
      'status' => FALSE,
    ]);

    // Create a second feed type.
    $feed_type2 = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $this->createFeed($feed_type2->id(), [
      'title' => 'Qux',
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);

    // Import feeds using drush.
    $this->drush('feeds:import-all');
    $this->assertStringContainsString('Foo: Created 25 Article items.', $this->getErrorOutput());
    $this->assertStringContainsString('Bar: Created 6 Article items.', $this->getErrorOutput());
    $this->assertStringNotContainsString('Baz', $this->getErrorOutput());
    $this->assertStringContainsString('Qux: Created 2 Article items.', $this->getErrorOutput());

    $this->assertNodeCount(33);
    $node = Node::load(1);
    $this->assertEquals('Adaptivethemes: Why I killed Node, may it RIP', $node->title->value);
  }

  /**
   * Tests importing all feeds of all types, including disabled ones.
   *
   * @covers ::importAllFeeds
   */
  public function testImportAllFeedsIncludingDisabled() {
    // Create feeds.
    $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $this->createFeed($this->feedType->id(), [
      'title' => 'Bar',
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Create a disabled feed that should be imported.
    $this->createFeed($this->feedType->id(), [
      'title' => 'Baz',
      'source' => $this->resourcesPath() . '/atom/entries.atom',
      'status' => FALSE,
    ]);

    // Create a second feed type.
    $feed_type2 = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $this->createFeed($feed_type2->id(), [
      'title' => 'Qux',
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);

    // Import feeds using drush.
    $this->drush('feeds:import-all --import-disabled');
    $this->assertStringContainsString('Foo: Created 25 Article items.', $this->getErrorOutput());
    $this->assertStringContainsString('Bar: Created 6 Article items.', $this->getErrorOutput());
    $this->assertStringContainsString('Baz: Created 3 Article items.', $this->getErrorOutput());
    $this->assertStringContainsString('Qux: Created 2 Article items.', $this->getErrorOutput());

    $this->assertNodeCount(36);
    $node = Node::load(1);
    $this->assertEquals('Adaptivethemes: Why I killed Node, may it RIP', $node->title->value);
  }

  /**
   * Tests importing all feeds of one specific type.
   *
   * @covers ::importAllFeeds
   */
  public function testImportAllFeedsOfOneType() {
    // Create two feeds.
    $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $this->createFeed($this->feedType->id(), [
      'title' => 'Bar',
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Create a second feed type.
    $feed_type2 = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $this->createFeed($feed_type2->id(), [
      'title' => 'Qux',
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);

    // Import feeds using drush.
    $this->drush('feeds:import-all ' . $feed_type2->id());
    $this->assertStringNotContainsString('Foo', $this->getErrorOutput());
    $this->assertStringNotContainsString('Bar', $this->getErrorOutput());
    $this->assertStringContainsString('Qux: Created 2 Article items.', $this->getErrorOutput());

    $this->assertNodeCount(2);
    $node = Node::load(1);
    $this->assertEquals('Lorem ipsum', $node->title->value);
  }

  /**
   * Tests importing all feeds of two specific types.
   *
   * @covers ::importAllFeeds
   */
  public function testImportAllFeedsOfTwoTypes() {
    // Create two feeds.
    $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);
    $this->createFeed($this->feedType->id(), [
      'title' => 'Bar',
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Create a second feed type.
    $feed_type2 = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);
    $this->createFeed($feed_type2->id(), [
      'title' => 'Qux',
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);

    // Create a third feed type.
    $feed_type3 = $this->createFeedTypeForCsv([
      'guid' => 'GUID',
      'title' => 'Title',
    ]);
    $this->createFeed($feed_type3->id(), [
      'title' => 'Thud',
      'source' => $this->resourcesPath() . '/csv/nodes.csv',
    ]);

    // Import feeds using drush.
    $this->drush('feeds:import-all ' . $this->feedType->id() . ' ' . $feed_type3->id());
    $this->assertStringContainsString('Foo: Created 25 Article items.', $this->getErrorOutput());
    $this->assertStringContainsString('Bar: Created 6 Article items.', $this->getErrorOutput());
    $this->assertStringNotContainsString('Qux', $this->getErrorOutput());
    $this->assertStringContainsString('Thud: Created 8 Article items.', $this->getErrorOutput());

    $this->assertNodeCount(39);
    $node = Node::load(1);
    $this->assertEquals('Adaptivethemes: Why I killed Node, may it RIP', $node->title->value);
  }

  /**
   * @covers ::lockFeed
   */
  public function testLockFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
    ]);
    $this->assertFalse($feed->isLocked());

    $this->drush('feeds:lock', [$feed->id()]);
    $this->assertTrue($feed->isLocked());
    $this->assertStringContainsString('The feed "Foo" has been locked.', $this->getErrorOutput());

    // Try to lock it again.
    $this->drush('feeds:lock', [$feed->id()]);
    $this->assertStringContainsString('This feed is already locked.', $this->getErrorOutput());
  }

  /**
   * @covers ::unlockFeed
   */
  public function testUnlockFeed() {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'title' => 'Foo',
    ]);
    $feed->lock();

    $this->assertTrue($feed->isLocked());

    $this->drush('feeds:unlock', [$feed->id()]);
    $this->assertFalse($feed->isLocked());
    $this->assertStringContainsString('The feed "Foo" has been unlocked.', $this->getErrorOutput());

    // Try to unlock it again.
    $this->drush('feeds:unlock', [$feed->id()]);
    $this->assertStringContainsString('This feed is already unlocked.', $this->getErrorOutput());
  }

  /**
   * Tests commands that require a feed ID.
   *
   * @param string $expected_output
   *   The expected output.
   * @param string $command
   *   The command to execute.
   * @param array $args
   *   (optional) Command arguments.
   * @param array $options
   *   (optional) An associative array containing options.
   *
   * @dataProvider providerFeed
   */
  public function testFeedCommandFailures($expected_output, $command, array $args = [], array $options = []) {
    // Create a feed.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/drupalplanet.rss2',
    ]);

    $this->drush($command, $args, $options, NULL, NULL, 1);

    // Assert that no nodes got imported.
    $this->assertNodeCount(0);

    // Assert the output.
    $this->assertStringContainsString($expected_output, $this->getErrorOutput());
  }

  /**
   * Data provider for ::testFeedCommandFailures().
   */
  public function providerFeed() {
    $return = [];

    $commands = [
      'feeds:enable',
      'feeds:disable',
      'feeds:import',
      'feeds:lock',
      'feeds:unlock',
    ];
    foreach ($commands as $command) {
      $return[$command . ':no-feed'] = [
        'expected_output' => 'Please specify the ID of the feed',
        'command' => $command,
      ];
      $return[$command . ':non-existing-feed'] = [
        'expected_output' => 'There is no feed with id 25',
        'command' => $command,
        'args' => [25],
      ];
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSimplifiedErrorOutput() {
    // Remove \n from output.
    $output = $this->traitGetSimplifiedErrorOutput();
    return str_replace("\n", '', $output);
  }

}
