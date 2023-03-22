<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\FeedsItem;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\FeedsItem
 * @group feeds
 */
class FeedsItemTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'feeds_item';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return FeedsItem::class;
  }

}
