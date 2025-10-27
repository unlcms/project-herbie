<?php

namespace Drupal\unl_events\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides an Upcoming Events block.
 *
 * @Block(
 *   id = "unl_upcoming_events",
 *   admin_label = @Translation("Upcoming Events"),
 *   category = @Translation("Aggregated Content"),
 * )
 */
class UpcomingEventsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quantity' => 6,
      'url' => '',
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
        '6' => 6,
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['quantity'],
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Calendar URL'),
      '#required' => TRUE,
      '#description' => $this->t('The URL of the UNL Events calendar'),
      '#default_value' => $this->configuration['url'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['quantity'] = $form_state->getValue('quantity');
    $this->configuration['url'] = $form_state->getValue('url');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $limit = $this->configuration['quantity'];
    $url = $this->configuration['url'];
    // Add trailing slash to URL is one is not present.
    $url = (substr($url, -1) !== '/') ? $url . '/' : $url;

    $widget_config = [
      'limit' => $limit,
      'url' => $url,
    ];

    return [
      '#theme' => 'unl_events_upcoming_events_block',
      '#attributes' => new Attribute(
        [
          'class' => [
            'unl-upcoming-events',
          ],
        ],
      ),
      '#calendar_limit' => $limit,
      '#calendar_url' => $url,
      '#json_config_string' => json_encode($widget_config),
    ];
  }

}
