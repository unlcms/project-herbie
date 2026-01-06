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
    $configuration = $this->getConfiguration();
    $active_theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();

    if($active_theme == "unl_six_herbie") {
      $build['#settings']['grid_wrapper_attributes']['class'] = [
        'dcf-d-grid',
        'dcf-grid-cols-1',
        'dcf-grid-cols-2@sm',
        'dcf-grid-cols-3@md',
        'dcf-col-gap-vw',
      ];
    } else {
      $build['#settings']['grid_wrapper_attributes']['class'] = [
        'dcf-grid-thirds@md',
        'dcf-col-gap-vw',
        'dcf-row-gap-5',
      ];
    }

    // Loop through each region and set grid column classes.
    foreach ($regions as $key => $value) {
      if (isset($configuration['column_classes'])) {
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
      '33-33-33' => '33%/33%/33%',
    ];
  }

}
