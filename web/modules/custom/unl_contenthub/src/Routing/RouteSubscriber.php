<?php

namespace Drupal\unl_contenthub\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // On the Content Hub site itself, don't allow Entity Share Client actions.
    if (!User::load(\Drupal::currentUser()->id())->hasRole('super_administrator')) {
      if ($route = $collection->get('entity_share_client.admin_content_page')) {
        $route->setRequirement('_access', $this->allowContentHubPull());
      }
      if ($route = $collection->get('entity_share_client.admin_content_pull_form')) {
        $route->setRequirement('_access', $this->allowContentHubPull());
      }
    }
  }

  /**
   * Determines if the site is an instance of Content Hub.
   *
   * @return bool
   */
  protected function allowContentHubPull() {
    $siteBase = rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),'/');

    $remotes = \Drupal::entityTypeManager()->getStorage('remote')->loadMultiple();

    foreach($remotes as $label => $remote) {
      if ($remote->get('url') == $siteBase) {
        return 'FALSE';
      }
    }

    return 'TRUE';
  }

}
