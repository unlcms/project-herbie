<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Item;

use Drupal\feeds\Feeds\Item\DynamicItem;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Item\DynamicItem
 * @group feeds
 */
class DynamicItemTest extends ItemTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->item = new DynamicItem();
  }

}
