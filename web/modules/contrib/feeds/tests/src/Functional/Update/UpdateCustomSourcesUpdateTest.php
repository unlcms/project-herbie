<?php

namespace Drupal\Tests\feeds\Functional\Update;

use Drupal\feeds\Entity\FeedType;

/**
 * Provides tests for updating custom sources in feed types.
 *
 * @group feeds
 * @group Update
 * @group legacy
 */
class UpdateCustomSourcesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->getCoreFixturePath(8),
      __DIR__ . '/../../../fixtures/feeds-8.x-3.0-alpha6-feeds_installed.php',
      __DIR__ . '/../../../fixtures/feeds-8.x-3.0-alpha11/feed_type.custom-sources-without-type.php',
    ];
  }

  /**
   * Tests updating existing custom sources on feed types.
   */
  public function testUpdateCustomSources() {
    // Run the updates.
    $this->runUpdates();

    // Check that for all our feed types, custom sources now have a type.
    $custom_source_type_map = [
      'csv' => 'csv',
    ];
    foreach ($custom_source_type_map as $feed_type_id => $expected_custom_source_type) {
      $feed_type = FeedType::load($feed_type_id);
      // Check that all custom sources now have a type specified.
      foreach ($feed_type->getCustomSources() as $custom_source) {
        $this->assertEquals($expected_custom_source_type, $custom_source['type']);
      }
    }
  }

}
