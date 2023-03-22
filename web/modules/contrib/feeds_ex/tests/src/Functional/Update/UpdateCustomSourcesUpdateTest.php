<?php

namespace Drupal\Tests\feeds_ex\Functional\Update;

use Drupal\feeds\Entity\FeedType;
use Drupal\Tests\feeds\Functional\Update\UpdatePathTestBase;

/**
 * Provides tests for updating custom sources in feed types.
 *
 * @group feeds_ex
 * @group Update
 * @group legacy
 */
class UpdateCustomSourcesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['feeds', 'feeds_ex', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      $this->getCoreFixturePath(9),
      __DIR__ . '/../../../fixtures/feeds_ex-8.x-1.0-alpha5-installed.php',
      __DIR__ . '/../../../fixtures/feeds_ex-8.x-1.0-alpha5/feed_type.custom-sources-without-type.php',
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
      'html' => 'xml',
      'xml' => 'xml',
      'jmespath' => 'json',
      'jmespathlines' => 'json',
      'jsonpath' => 'json',
      'jsonpathlines' => 'json',
      'querypathhtml' => 'querypathxml',
      'querypathxml' => 'querypathxml',
    ];
    foreach ($custom_source_type_map as $feed_type_id => $expected_custom_source_type) {
      $feed_type = FeedType::load($feed_type_id);
      // Check that all custom sources now have a type specified.
      foreach ($feed_type->getCustomSources() as $custom_source) {
        $this->assertEquals($expected_custom_source_type, $custom_source['type']);
      }

      // Check that redundant configuration has been removed.
      $this->assertArrayNotHasKey('sources', $feed_type->getParser()->getConfiguration());
      $this->assertArrayNotHasKey('debug_mode', $feed_type->getParser()->getConfiguration());
    }
  }

}
