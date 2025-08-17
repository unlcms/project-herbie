<?php

namespace Drupal\dcf_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

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
    $configuration = $this->getConfiguration();

    $build['#settings']['grid_wrapper_attributes']['class'] = [
      'dcf-grid',
      'dcf-col-gap-vw',
      'dcf-row-gap-5',
    ];

    $widths = explode('-', $configuration['column_widths']);

    $build['first']['#attributes']['class'][] = 'dcf-col-' . $widths[0] . '%-start@md';
    $build['first']['#attributes']['class'][] = 'dcf-col-100%';
    $build['first']['#attributes']['class'][] = 'dcf-1st@md';

    $build['second']['#attributes']['class'][] = 'dcf-col-' . $widths[1] . '%-end@md';
    $build['second']['#attributes']['class'][] = 'dcf-col-100%';
    $build['second']['#attributes']['class'][] = 'dcf-2nd@md';

    // Loop through each region and set grid column classes.
    foreach ($regions as $key => $value) {
      if(isset($configuration['column_classes'])) {
        foreach ((array) $configuration['column_classes'] as $class) {
          if (!empty($class)) {
            $build[$key]['#attributes']['class'][] = $class;
          }
        }
      }
    }

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
