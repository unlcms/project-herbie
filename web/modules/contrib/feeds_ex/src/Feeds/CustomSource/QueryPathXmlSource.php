<?php

namespace Drupal\feeds_ex\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;

/**
 * A querypath XML source.
 *
 * @FeedsCustomSource(
 *   id = "querypathxml",
 *   title = @Translation("QueryPath XML"),
 * )
 */
class QueryPathXmlSource extends XmlSource {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'attribute' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['value']['#title'] = $this->t('QueryPath XML value');
    $form['attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribute name'),
      '#default_value' => $this->configuration['attribute'],
      '#description' => $this->t('Attribute on a XML or HTML tag.'),
      '#maxlength' => 1024,
      '#weight' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function additionalColumns(array $custom_source) {
    $column_definitions = [
      'attribute' => [
        '#header' => $this->t('Attribute name'),
        '#value' => [
          '#type' => 'item',
          '#markup' => $custom_source['attribute'] ?? '',
        ],
      ],
    ];

    return $column_definitions + parent::additionalColumns($custom_source);
  }

}
