<?php

namespace Drupal\unl_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Provides a Recent News block.
 *
 * @Block(
 *   id = "unl_recent_news",
 *   admin_label = @Translation("Recent News"),
 *   category = @Translation("Aggregated Content"),
 * )
 */
class RecentNewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quantity' => 4,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Items to display'),
      '#options' => [
        '3' => 3,
        '4' => 4,
      ],
      '#description' => $this->t('The numbers of news items to display in this block'),
      '#default_value' => $this->configuration['quantity'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['quantity'] = $form_state
      ->getValue('quantity');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view = Views::getView('news_recent');
    $view->setDisplay('block_1');
    $view->setItemsPerPage($this->configuration['quantity']);
    $view->preExecute();
    $view->execute();

    return $view->buildRenderable();
  }

}
