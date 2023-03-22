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
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'column_classes' => [
        'col_1' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Merge in column classes form array.
    $form = array_merge_recursive($form, $this->columnClassFormElements(1));
    $form_state->set('column_count', 1);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    $column_widths = $this->configuration['column_widths'];
    switch ($column_widths) {
      case '100':
        break;

      case '75-centered':
        $build['#attributes']['class'] = array_merge($build['#attributes']['class'], [
          'unl-dcf-onecol-75-centered',
        ]);
        break;
    }

    $column_classes = $this->configuration['column_classes'];

    foreach ($column_classes['col_1'] as $class) {
      $build['first']['#attributes']['class'][] = $class;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '100' => '100%',
      '75-centered' => '75% (centered)',
    ];
  }

}
