<?php

namespace Drupal\unl_views\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class ProductionAccess {

  /**
   * _custom_access callback to help prevent editing of specified views on Production.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultReasonInterface
   */
  public function viewAccess(AccountInterface $account) {
    if (\Drupal::service('module_handler')->moduleExists('config_split')) {
      $enabled_environments = $this->getCurrentConfigSplit();
      // If we're on a Development instance, allow editing all views.
      if (!in_array('Production', $enabled_environments)) {
        return AccessResult::allowedIfHasPermission($account, 'administer views');
      }
    }

    $current_path = \Drupal::service('path.current')->getPath();

    // These are the view machine names that should not be editable in Production.
    $forbidden_views = [
      'content',
      'user_admin_people',
    ];

    foreach ($forbidden_views as $view_id) {
      // @TODO Improve this to avoid a false positive on something like 'content_recent'
      if (strpos($current_path,'/admin/structure/views/view/'.$view_id) === 0) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowedIfHasPermission($account, 'administer views');
  }

  /**
   * Returns array of enabled Config Split environments.
   * @TODO Move this to its own module and provide a service since it
   *   doesn't appear the config_split module does.
   * Below code is copied from config_split_form_config_admin_import_form_alter().
   *
   * @return array
   */
  public function getCurrentConfigSplit() {
    $enabled = [];
    $config_split_entities = \Drupal::entityTypeManager()
      ->getStorage('config_split')
      ->loadMultiple();
    $active_filters = \Drupal::service('plugin.manager.config_filter')
      ->getDefinitions();
    $active_filters = array_filter($active_filters, function ($filter) {
      return $filter['status'];
    });
    foreach ($config_split_entities as $config_split_entity) {
      if (in_array('config_split:' . $config_split_entity->id(), array_keys($active_filters))) {
        $enabled[] = $config_split_entity->label();
      }
    }
    return $enabled;
  }
}
