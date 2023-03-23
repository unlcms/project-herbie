<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configurable three column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class ThreeColumnLayout extends DcfLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'] = array_merge($build['#attributes']['class'], [
      'dcf-grid-thirds@md',
      'dcf-col-gap-vw',
      'dcf-row-gap-5',
    ]);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '33-33-33' => '33%/33%/33%',
    ];
  }

}
