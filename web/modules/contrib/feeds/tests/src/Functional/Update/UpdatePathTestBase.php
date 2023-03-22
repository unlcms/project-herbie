<?php

namespace Drupal\Tests\feeds\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase as CoreUpdatePathTestBase;

/**
 * Base class for Feeds update tests.
 */
abstract class UpdatePathTestBase extends CoreUpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['feeds', 'node'];

  /**
   * Returns the path to the Drupal core fixture.
   *
   * @param int $core_version
   *   The oldest core version to get.
   *
   * @return string
   *   A path to the drupal core fixture.
   */
  protected function getCoreFixturePath(int $core_version = 9): string {
    $fixtures[8] = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
    $fixtures[9] = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.3.0.bare.standard.php.gz',
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];

    switch ($core_version) {
      case 8:
        $fixtures = array_merge($fixtures[8], $fixtures[9]);
        break;

      default:
        $fixtures = $fixtures[9];
        break;
    }

    foreach ($fixtures as $fixture) {
      if (file_exists($fixture)) {
        return $fixture;
      }
    }

    throw new \Exception('No suitable core fixture found. Please adjust ' . __CLASS__ . ' as necessary.');
  }

}
