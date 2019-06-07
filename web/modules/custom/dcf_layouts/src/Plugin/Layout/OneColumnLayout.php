<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

/**
 * Configurable one column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class OneColumnLayout extends DcfLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '100' => '100%',
    ];
  }

}
