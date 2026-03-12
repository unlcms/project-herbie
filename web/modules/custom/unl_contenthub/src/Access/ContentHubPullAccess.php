<?php

namespace Drupal\unl_contenthub\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class ContentHubPullAccess {
  public static function access(AccountInterface $account) {
    // Allow super administrators.
    if ($account->hasRole('super_administrator')) {
      return AccessResult::allowed();
    }

    $request = \Drupal::request();
    $siteBase = rtrim($request->getSchemeAndHttpHost(), '/');

    $remotes = \Drupal::entityTypeManager()->getStorage('remote')->loadMultiple();

    foreach ($remotes as $remote) {
      if ($remote->get('url') == $siteBase) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }
}
