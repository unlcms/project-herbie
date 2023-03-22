<?php
/**
 * @file
 * Contains \Drupal\unl_multisite\Controller\UnlMultisiteController.
 */

namespace Drupal\unl_multisite\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for unl_multisite routes.
 */
class UnlMultisiteController extends ControllerBase {

/**
 * Returns an administrative overview of all sites.
 *
 * @return array
 *   A render array representing the administrative page content.
 */
  public function XXsitesOverview() {

    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World!'),
    );
    return $build;
  }
}
