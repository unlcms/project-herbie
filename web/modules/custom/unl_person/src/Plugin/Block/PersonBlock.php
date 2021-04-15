<?php

namespace Drupal\unl_person\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides a Person List block.
 *
 * @Block(
 *   id = "unl_person_list",
 *   admin_label = @Translation("Person"),
 *   category = @Translation("Curated Lists"),
 * )
 */
class PersonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'persons' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $person_nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['type' => 'person', 'status' => 1]);
    $options = [];
    foreach ($person_nodes as $node) {
      $options[$node->id()] = $node->label();
    }

    $form['persons'] = [
      '#type' => 'select',
      '#title' => $this->t('Persons to display'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['persons'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['persons'] = $form_state
      ->getValue('persons');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'unl_person_person_list',
      '#attributes' => new Attribute(
        [
          'class' => [
            'unl-person-list',
          ],
        ],
      ),
      '#nodes' => $this->configuration['persons'],
    ];
  }
}
