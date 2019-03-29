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
      'layout',
      $this->getPluginDefinition()->getTemplate(),
    ];

    $build['first']['#attributes']['class'][] = 'dcf-col';
    $build['second']['#attributes']['class'][] = 'dcf-col';
    $build['third']['#attributes']['class'][] = 'dcf-col';
    $build['fourth']['#attributes']['class'][] = 'dcf-col';

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
