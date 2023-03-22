<?php

namespace Drupal\Tests\feeds_log\Kernel;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * Provides a base class for Feeds Log kernel tests.
 */
abstract class FeedsLogKernelTestBase extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'options',
    'feeds_log',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install database schemes.
    $this->installEntitySchema('feeds_import_log');
    $this->installEntitySchema('view');
    $this->installConfig('feeds_log');
    $this->installConfig('views');
    $this->installSchema('feeds_log', 'feeds_import_log_entry');
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
