<?php

namespace Drupal\unl_system\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PrimaryBaseUrlSubscriber implements EventSubscriberInterface {

  public function checkBasePath(RequestEvent $event) {
    // Skip processing if the domain is cms-staging.unl.edu.
    if ($event->getRequest()->getHost() === 'cms-staging.unl.edu') {
      return;
    }

    $config_factory = \Drupal::service('config.factory');
    $config = $config_factory->get('unl_system.settings');
    $primary_base_url = $config->get('primary_base_url');

    if ($primary_base_url && PHP_SAPI != 'cli') {
      if (substr($primary_base_url, -1) != '/') {
        $primary_base_url .= '/';
      }

      $front_url = \Drupal::urlGenerator()->generateFromRoute('<front>', [], ['absolute' => TRUE]);

      // Modify $primary_base_url to make sure it is the same base.
      $current_url_schema = parse_url($front_url, PHP_URL_SCHEME);
      $primary_base_url_schema = parse_url($primary_base_url, PHP_URL_SCHEME);
      $primary_base_url = $current_url_schema . substr($primary_base_url, strlen($primary_base_url_schema));

      // If on an alternative domain, redirect to the Primary Base URL.
      if ($primary_base_url != $front_url) {
        $path = $event->getRequest()->getPathInfo();
        $redirect_url = rtrim($primary_base_url, '/') . $path;
        $query = $event->getRequest()->getQueryString();
        if ($query) {
          $redirect_url = $redirect_url . '?' . $query;
        }

        $response = new TrustedRedirectResponse($redirect_url, '301');
        // Fire the response immediately rather than using $event->setResponse($response)
        // and letting other modules like Redirect mess with it.
        $response->send();
        exit;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkBasePath');
    return $events;
  }

}
