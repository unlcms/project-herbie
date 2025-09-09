<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configurable four column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class FourColumnLayout extends DcfLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $active_theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();

    if ($active_theme == "unl_six_herbie") {
      $build['#settings']['grid_wrapper_attributes']['class'] = [
        'dcf-d-grid',
        'dcf-grid-cols-1',
        'dcf-grid-cols-2@sm',
        'dcf-grid-cols-4@md',
        'dcf-col-gap-vw',
      ];
    } else {
      $build['#settings']['grid_wrapper_attributes']['class'] = [
        'dcf-grid-halves@sm',
        'dcf-grid-fourths@lg',
        'dcf-col-gap-vw',
        'dcf-row-gap-5',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '25-25-25-25' => '25%/25%/25%/25%',
    ];
  }
}
