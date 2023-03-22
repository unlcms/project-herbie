<?php

namespace Drupal\file_delete;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessControlHandler as BaseFileAccessControlHandler;

/**
 * Extends File access control to allow easily deleting files.
 */
class FileAccessControlHandler extends BaseFileAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // For any other operation, pass to default File access handler.
    if ($operation !== 'delete') {
      return parent::checkAccess($entity, $operation, $account);
    }

    // Check if User has our delete files permission.
    $result = AccessResult::allowedIfHasPermission($account, 'delete files');
    if ($result->isAllowed()) {
      return $result;
    }

    // Otherwise, pass to default File handler.
    return parent::checkAccess($entity, $operation, $account);
  }

}
