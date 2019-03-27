<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

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
    $build['#attributes']['class'] = [
      'dcf-grid',
      'dcf-col-gap-vw',
      'layout',
      $this->getPluginDefinition()->getTemplate(),
    ];

    $widths = explode('-', $this->configuration['column_widths']);

    $build['first']['#attributes']['class'][] = 'dcf-col-'.$widths[0].'%-start@md';
    $build['first']['#attributes']['class'][] = 'dcf-col-100%';
    $build['first']['#attributes']['class'][] = 'dcf-1st@md';

    $build['second']['#attributes']['class'][] = 'dcf-col-'.$widths[1].'%@md';
    $build['second']['#attributes']['class'][] = 'dcf-col-100%';
    $build['second']['#attributes']['class'][] = 'dcf-2nd@md';

    $build['third']['#attributes']['class'][] = 'dcf-col-'.$widths[2].'%-end@md';
    $build['third']['#attributes']['class'][] = 'dcf-col-100%';
    $build['third']['#attributes']['class'][] = 'dcf-3rd@md';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '25-50-25' => '25%/50%/25%',
      '33-34-33' => '33%/34%/33%',
      '25-25-50' => '25%/25%/50%',
      '50-25-25' => '50%/25%/25%',
    ];
  }

}
