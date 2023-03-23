<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

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
  protected function getWidthOptions() {
    return [
      '100' => '100%',
    ];
  }

}
