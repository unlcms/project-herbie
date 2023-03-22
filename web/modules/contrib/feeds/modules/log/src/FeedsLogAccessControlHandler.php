<?php

namespace Drupal\feeds_log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the feeds_import_log entity.
 *
 * @see \Drupal\feeds_log\Entity\ImportLog
 */
class FeedsLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $feeds_import_log, $operation, AccountInterface $account) {
    // Permission to view any logs is required.
    $has_perm = $account->hasPermission('feeds_log.access');

    // View permission on the feed is required.
    $feed_view_access = $feeds_import_log->feed->entity->access('view', $account);

    return AccessResult::allowedIf($has_perm && $feed_view_access);
  }

}
