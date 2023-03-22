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
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'column_classes' => [
        'col_1' => [],
        'col_2' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Merge in column classes form array.
    $form = array_merge_recursive($form, $this->columnClassFormElements(2));
    $form_state->set('column_count', 2);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'] = array_merge($build['#attributes']['class'], [
      'dcf-grid',
      'dcf-col-gap-vw',
      'dcf-row-gap-5',
    ]);

    $widths = explode('-', $this->configuration['column_widths']);
    $column_classes = $this->configuration['column_classes'];

    $build['first']['#attributes']['class'][] = 'dcf-col-' . $widths[0] . '%-start@md';
    $build['first']['#attributes']['class'][] = 'dcf-col-100%';
    $build['first']['#attributes']['class'][] = 'dcf-1st@md';
    foreach ($column_classes['col_1'] as $class) {
      $build['first']['#attributes']['class'][] = $class;
    }

    $build['second']['#attributes']['class'][] = 'dcf-col-' . $widths[1] . '%-end@md';
    $build['second']['#attributes']['class'][] = 'dcf-col-100%';
    $build['second']['#attributes']['class'][] = 'dcf-2nd@md';
    foreach ($column_classes['col_2'] as $class) {
      $build['second']['#attributes']['class'][] = $class;
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
