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
    $active_theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    $build = parent::build($regions);
    $column_width = $this->configuration['column_widths'];
    $widths = explode('-', $this->configuration['column_widths']);

    if ($active_theme == "unl_six_herbie") {
      if ($column_width == '50-50') {
        $build['#settings']['grid_wrapper_attributes']['class'] = [
          'dcf-d-grid',
          'dcf-grid-cols-1',
          'dcf-grid-cols-2@sm',
          'dcf-col-gap-vw',
        ];
      } elseif ($column_width == '33-67' || $column_width == '67-33' || $column_width == '75-25' || $column_width == '25-75' || $column_width == '75-25') {
        $build['#settings']['grid_wrapper_attributes']['class'] = [
          'dcf-d-grid',
          'dcf-grid-cols-12',
          'dcf-col-gap-vw',
          'dcf-row-gap-6',
        ];

        if ($column_width == "33-67") {
          $build['first']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-4@md";
          $build['second']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-8@md";
        }
        if ($column_width == "67-33") {
          $build['first']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-8@md";
          $build['second']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-4@md";
        }
        if ($column_width == "25-75") {
          $build['first']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-3@md";
          $build['second']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-9@md";
        }
        if ($column_width == "75-25") {
          $build['first']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-9@md";
          $build['second']['#attributes']['class'][] = "dcf-col-span-12 dcf-col-span-3@md";
        }
      }
    } else {
      $build['#settings']['grid_wrapper_attributes']['class'] = [
        'dcf-grid',
        'dcf-col-gap-vw',
        'dcf-row-gap-5',
      ];

      $build['first']['#attributes']['class'][] = 'dcf-col-' . $widths[0] . '%-start@md';
      $build['first']['#attributes']['class'][] = 'dcf-col-100%';
      $build['first']['#attributes']['class'][] = 'dcf-1st@md';

      $build['second']['#attributes']['class'][] = 'dcf-col-' . $widths[1] . '%-end@md';
      $build['second']['#attributes']['class'][] = 'dcf-col-100%';
      $build['second']['#attributes']['class'][] = 'dcf-2nd@md';
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
