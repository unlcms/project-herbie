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
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'column_classes' => [
        'col_1' => [],
        'col_2' => [],
        'col_3' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Merge in column classes form array.
    $form = array_merge_recursive($form, $this->columnClassFormElements(3));
    $form_state->set('column_count', 3);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attributes']['class'] = array_merge($build['#attributes']['class'], [
      'dcf-grid-thirds@md',
      'dcf-col-gap-vw',
      'dcf-row-gap-5',
    ]);

    $column_classes = $this->configuration['column_classes'];

    foreach ($column_classes['col_1'] as $class) {
      $build['first']['#attributes']['class'][] = $class;
    }
    foreach ($column_classes['col_2'] as $class) {
      $build['second']['#attributes']['class'][] = $class;
    }
    foreach ($column_classes['col_3'] as $class) {
      $build['third']['#attributes']['class'][] = $class;
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
