<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'feeds_item_guid' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_item_guid",
 *   label = @Translation("GUID of the feed item"),
 *   field_types = {
 *     "feeds_item"
 *   }
 * )
 */
class FeedsItemGuidFormatter extends FeedsItemFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      if ($this->valueIsUrl($item->guid)) {
        try {
          $url = Url::fromUri($item->guid);
          $element[$delta] = $this->generateLink($url);
        }
        catch (\InvalidArgumentException $e) {
          // Value is not an url, render as plain text instead.
          $element[$delta] = ['#plain_text' => $item->guid];
        }
      }
      elseif (strlen($item->guid)) {
        $element[$delta] = ['#plain_text' => $item->guid];
      }
    }

    return $element;
  }

}
