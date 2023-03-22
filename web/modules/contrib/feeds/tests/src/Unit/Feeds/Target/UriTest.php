<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Uri;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Uri
 * @group feeds
 */
class UriTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'uri';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Uri::class;
  }

}
