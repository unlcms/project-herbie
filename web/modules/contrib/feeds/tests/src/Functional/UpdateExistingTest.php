<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;

/**
 * Tests the feature of updating items.
 *
 * @group feeds
 */
class UpdateExistingTest extends FeedsBrowserTestBase {

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
    $this->feedType = $this->createFeedTypeForCsv(['title' => 'title'], [
      'processor_configuration' => [
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
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
      ],
    ]);
  }

  /**
   * Tests updating a node that is unpublished.
   */
  public function testUpdateUnpublishedNodeWithNodeAccess() {
    // Enable a node access module and rebuild permissions. Set an ID for
    // node_access_test.no_access_uid, so the anonymous user doesn't bypass node
    // access.
    \Drupal::state()->get('node_access_test.no_access_uid', 1);
    $this->container->get('module_installer')->install(['node_access_test']);
    node_access_rebuild();

    // Create an user with limited privileges.
    $account = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
    ]);

    // Set this account to be the owner of the entities.
    $processor = $this->feedType->getProcessor();
    $config = $processor->getConfiguration();
    $config['owner_id'] = $account->id();
    $processor->setConfiguration($config);
    $this->feedType->save();

    // Create an article that is not published.
    $node = Node::create([
      'title'  => 'Lorem ipsum',
      'type'  => 'article',
      'uid'  => $this->adminUser->id(),
      'status' => 0,
    ]);
    $node->save();

    // Create a feed and import file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'uid' => $account->id(),
    ]);
    $feed->startCronImport();

    // Run cron to import.
    $this->cronRun();

    // Assert that two nodes exist in total.
    $this->assertNodeCount(2);

    // Reload feed and assert that 1 node got created and 1 node got updated.
    $feed = $this->reloadFeed($feed);
    $this->drupalGet('feed/1');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Created 1 article');
    $assert_session->pageTextContains('Updated 1 article');
  }

}
