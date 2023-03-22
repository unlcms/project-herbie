<?php

namespace Drupal\feeds_test_plugin\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Feeds\CustomSource\BlankSource;

/**
 * A custom source called "Foo".
 *
 * @FeedsCustomSource(
 *   id = "foo",
 *   title = @Translation("Foo"),
 * )
 */
class FooSource extends BlankSource {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'propbool' => FALSE,
      'proptext' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['propbool'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Boolean value'),
      '#default_value' => $this->configuration['propbool'],
    ];
    $form['proptext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text value'),
      '#default_value' => $this->configuration['proptext'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('proptext') == 'Illegal value') {
      $form_state->setError($form['proptext'], $this->t('The textfield contains "Illegal value".'));
    }
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function additionalColumns(array $custom_source) {
    $column_definitions = [
      'propbool' => [
        '#header' => $this->t('Boolean value'),
        '#value' => [
          '#markup' => $custom_source['propbool'] ? $this->t('Enabled') : $this->t('Disabled'),
        ],
      ],
      'proptext' => [
        '#header' => $this->t('Text value'),
        '#value' => [
          '#markup' => $custom_source['proptext'],
        ],
      ],
    ];

    return $column_definitions;
  }

}
