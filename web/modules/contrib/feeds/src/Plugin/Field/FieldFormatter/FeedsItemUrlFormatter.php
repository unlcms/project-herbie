<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds\FeedsItemInterface;

/**
 * Plugin implementation of the 'feeds_item_url' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_item_url",
 *   label = @Translation("URL of the feed item"),
 *   field_types = {
 *     "feeds_item"
 *   }
 * )
 */
class FeedsItemUrlFormatter extends FeedsItemFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'url_plain' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['url_plain'] = [
      '#title' => $this->t('Display URL as plain text'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('url_plain'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    if (!empty($settings['url_plain'])) {
      $summary[] = $this->t('Show URL as plain-text');
    }
    else {
      $summary[] = $this->t('Show URL as link');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      try {
        $url = $this->buildUrl($item);
        $element[$delta] = $this->generateLink($url);
      }
      catch (\InvalidArgumentException $e) {
        // Value is not an url, continue to next item.
        continue;
      }
    }

    return $element;
  }

  /**
   * Builds the \Drupal\Core\Url object for a feeds_item field item.
   *
   * @param \Drupal\feeds\FeedsItemInterface $item
   *   The feeds_item field item being rendered.
   *
   * @return \Drupal\Core\Url
   *   An Url object.
   */
  protected function buildUrl(FeedsItemInterface $item) {
    return $item->getUrl() ?: Url::fromRoute('<none>');
  }

  /**
   * {@inheritdoc}
   */
  public function generateLink(Url $url) {
    if ($this->getSetting('url_plain')) {
      // Render url as plain text.
      return [
        '#plain_text' => $url->toString(),
      ];
    }
    return parent::generateLink($url);
  }

}
