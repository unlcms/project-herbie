<?php

namespace Drupal\Tests\feeds_log\Functional;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Provides a base class for Feeds Log functional tests.
 */
abstract class FeedsLogBrowserTestBase extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'feeds',
    'feeds_log',
    'node',
    'user',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create a user with Feeds log admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'access feed overview',
      'feeds_log.access',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Returns logged entries.
   *
   * @param int $import_id
   *   The import ID to get log entries for.
   *
   * @return \stdClass[]
   *   A list of log entries.
   */
  protected function getLogEntries(int $import_id = NULL): array {
    $query = $this->container->get('database')
      ->select('feeds_import_log_entry')
      ->fields('feeds_import_log_entry', []);

    if ($import_id) {
      $query->condition('import_id', 2);
    }

    return $query->execute()
      ->fetchAllAssoc('lid');
  }

}
