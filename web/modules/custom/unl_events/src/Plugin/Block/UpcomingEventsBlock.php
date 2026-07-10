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
      'url' => 'https://events.unl.edu/',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of items to display'),
      '#options' => [
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        '10' => 10,
        '11' => 11,
        '12' => 12,
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['quantity'],
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Calendar URL'),
      '#required' => TRUE,
      '#description' => $this->t('The URL of the UNL Events calendar. Example: https://events.unl.edu/career_services'),
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
