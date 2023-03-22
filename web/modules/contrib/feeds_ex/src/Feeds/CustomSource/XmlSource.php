<?php

namespace Drupal\feeds_ex\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\CustomSource\CustomSourceBase;

/**
 * A XML Xpath source.
 *
 * @FeedsCustomSource(
 *   id = "xml",
 *   title = @Translation("XML Xpath"),
 * )
 */
class XmlSource extends CustomSourceBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'value' => '',
      'machine_name' => '',
      'raw' => FALSE,
      'inner' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#machine_name_source'] = 'label';

    $form['label'] = [
      '#title' => $this->t('Administrative label'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['label'],
      '#weight' => -3,
    ];

    $form['value'] = [
      '#title' => $this->t('XPath value'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['value'],
      '#weight' => 1,
      '#description' => $this->t('The xpath query for the source field.'),
    ];

    if ($this->configuration['machine_name']) {
      $id = 'feeds-ex-xml-raw-' . $this->configuration['machine_name'];
      $form['machine_name'] = [
        '#type' => 'value',
        '#value' => $this->configuration['machine_name'],
      ];

      $form['label']['#required'] = TRUE;
      $form['value']['#required'] = TRUE;
    }
    elseif (isset($form['#delta']) && isset($form['#column'])) {
      $id = 'feeds-ex-xml-raw-' . $form['#delta'] . '-' . $form['#column'];
    }
    else {
      $id = 'feeds-ex-xml-raw';
    }

    $form['raw'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Raw value'),
      '#default_value' => $this->configuration['raw'],
      '#id' => $id,
      '#weight' => 3,
    ];
    $form['inner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inner XML'),
      '#default_value' => $this->configuration['inner'],
      '#states' => [
        'visible' => ['#' . $id => ['checked' => TRUE]],
      ],
      '#weight' => 4,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('value')) {
      if (!strlen($form_state->getValue('label'))) {
        $form_state->setError($form['label'], $this->t('The field %field is required.', [
          '%field' => $this->t('Administrative label'),
        ]));
      }
      if (!strlen($form_state->getValue('machine_name'))) {
        $form_state->setError($form['machine_name'], $this->t('The custom source must have a machine name.'));
      }
    }
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function additionalColumns(array $custom_source) {
    $column_definitions = [
      'raw' => [
        '#header' => $this->t('Raw value'),
        '#value' => [
          '#markup' => !empty($custom_source['raw']) ? $this->t('Enabled') : $this->t('Disabled'),
        ],
      ],
      'inner' => [
        '#header' => $this->t('Inner XML'),
        '#value' => [
          '#markup' => !empty($custom_source['inner']) ? $this->t('Enabled') : $this->t('Disabled'),
        ],
      ],
    ];

    return $column_definitions;
  }

}
