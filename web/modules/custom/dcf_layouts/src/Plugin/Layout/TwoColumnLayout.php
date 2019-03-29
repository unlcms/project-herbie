<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

/**
 * Configurable two column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class TwoColumnLayout extends DcfLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'] = [
      'dcf-grid',
      'dcf-col-gap-vw',
      'dcf-row-gap-5',
      'layout',
      $this->getPluginDefinition()->getTemplate(),
    ];

    $widths = explode('-', $this->configuration['column_widths']);

    $build['first']['#attributes']['class'][] = 'dcf-col-' . $widths[0] . '%-start@md';
    $build['first']['#attributes']['class'][] = 'dcf-col-100%';
    $build['first']['#attributes']['class'][] = 'dcf-1st@md';

    $build['second']['#attributes']['class'][] = 'dcf-col-' . $widths[1] . '%-end@md';
    $build['second']['#attributes']['class'][] = 'dcf-col-100%';
    $build['second']['#attributes']['class'][] = 'dcf-2nd@md';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '50-50' => '50%/50%',
      '33-67' => '33%/67%',
      '67-33' => '67%/33%',
      '25-75' => '25%/75%',
      '75-25' => '75%/25%',
    ];
  }

}
