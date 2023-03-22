<?php

namespace Drupal\feeds\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsItemListInterface;

/**
 * Defines an item list class for feeds item fields.
 */
class FeedsItemList extends EntityReferenceFieldItemList implements FeedsItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function getItemHashByFeed(FeedInterface $feed) {
    $item = $this->getItemByFeed($feed);
    return $item->hash ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemByFeed(FeedInterface $feed, $initialize = FALSE) {
    $index = $this->getItemIndex($feed);
    if ($index === FALSE) {
      if ($initialize) {
        return $this->appendItem(['target_id' => $feed->id()]);
      }
      return NULL;
    }
    return $this->offsetGet($index);
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(FeedInterface $feed) {
    if (!$this->hasItem($feed)) {
      $this->appendItem(['target_id' => $feed->id()]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem(FeedInterface $feed) {
    return $this->getItemIndex($feed) !== FALSE;
  }

  /**
   * Gets the index of the given feed item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed item.
   *
   * @return int|bool
   *   The index of the given feed item, or FALSE if not found.
   */
  protected function getItemIndex(FeedInterface $feed) {
    $values = $this->getValue();
    $feed_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($feed->id(), $feed_item_ids);
  }

}
