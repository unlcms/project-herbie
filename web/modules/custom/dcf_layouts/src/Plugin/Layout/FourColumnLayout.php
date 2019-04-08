<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

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
    $build['#attributes']['class'] = [
      'dcf-grid-halves@sm',
      'dcf-grid-fourths@lg',
      'dcf-col-gap-vw',
      'dcf-row-gap-5',
      'layout',
      $this->getPluginDefinition()->getTemplate(),
    ];
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
