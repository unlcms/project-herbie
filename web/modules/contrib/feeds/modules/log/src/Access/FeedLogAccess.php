<?php

namespace Drupal\feeds_log\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedInterface;

/**
 * Service for checking access for feeds logs.
 */
class FeedLogAccess {

  /**
   * Returns if logs may be accessed of the feed in question.
   *
   * @param \Drupal\feeds\FeedInterface $feeds_feed
   *   The feed to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The operating account.
   */
  public function access(FeedInterface $feeds_feed, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('feeds_log.access') && $feeds_feed->access('view'));
  }

}
