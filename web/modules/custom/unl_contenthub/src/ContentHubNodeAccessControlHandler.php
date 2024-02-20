<?php

namespace Drupal\unl_contenthub;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeAccessControlHandler;

/**
 * Extends the access control handler for the node entity type so that content
 * types that should only be created/edited on Content Hub can be restricted.
 *
 * @see \Drupal\node\Entity\Node
 * @ingroup node_access
 */
class ContentHubNodeAccessControlHandler extends NodeAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $node, $operation, AccountInterface $account) {
    if (($operation == 'update' || $operation == 'delete')
      && $account->hasPermission('edit any builder_page content')) {
      // Using 'edit any builder_page content' as the base permission for any
      // custom content type that may need editing. Because all permissions are
      // being managed with the herbie_roles feature, if a site creates a content
      // type the permissions will be blank because they are unable to set them.
      // This could have been overcome by granting everyone 'bypass node access'
      // ('Bypass content access control') but that would cause a problem in
      // trying to block off content types managed with Content Hub.
      return AccessResult::allowed();
    }

    return parent::checkAccess($node, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if (!$this->siteIsContentHub() && isset($entity_bundle)
      && $entity_bundle == 'major') {
      // Content belonging to this bundle needs to be created
      // at the Content Hub site.
      return AccessResult::forbidden();
    }
    elseif ($entity_bundle !== 'archive_page' &&
      $account->hasPermission('create builder_page content')) {
      // Using 'create builder_page content' as the base permission for any
      // custom content type that may get created. Because all permissions are
      // being managed with the herbie_roles feature, if a site creates a content
      // type the permissions will be blank because they are unable to set them.
      // This could have been overcome by granting everyone 'bypass node access'
      // ('Bypass content access control') but that would cause a problem in
      // trying to block off content types managed with Content Hub.
      return AccessResult::allowed();
    }

    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

  /**
   * Determines if the site is an instance of Content Hub.
   *
   * @return bool
   */
  protected function siteIsContentHub() {
    $siteBase = rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),'/');

    $remotes = \Drupal::entityTypeManager()->getStorage('remote')->loadMultiple();

    foreach($remotes as $label => $remote) {
      if ($remote->get('url') == $siteBase) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
