<?php

namespace Drupal\feeds;

/**
 * Defines an interface for the feeds item list.
 */
interface FeedsItemListInterface {

  /**
   * Adds a feed item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed item.
   *
   * @return $this
   */
  public function addItem(FeedInterface $feed);

  /**
   * Gets the feed item field.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param bool $initialize
   *   A flag to request an initialization of a feed item field.
   *
   * @return \Drupal\feeds\FeedsItemInterface|null
   *   A feed item or null if no initialization was requested.
   */
  public function getItemByFeed(FeedInterface $feed, $initialize = FALSE);

  /**
   * Gets the item hash by feed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   *
   * @return string
   *   The hash.
   */
  public function getItemHashByFeed(FeedInterface $feed);

  /**
   * Checks whether the entity field has a given feed item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed item.
   *
   * @return bool
   *   TRUE if the feed item was found, FALSE otherwise.
   */
  public function hasItem(FeedInterface $feed);

}
