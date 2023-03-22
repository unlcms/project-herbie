<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;

/**
 * Plugin implementation of the 'feeds_item_imported' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_item_imported",
 *   label = @Translation("Import timestamp of the feed item"),
 *   field_types = {
 *     "feeds_item"
 *   }
 * )
 */
class FeedsItemImportedFormatter extends TimestampFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $date_format = $this->getSetting('date_format');
    $custom_date_format = '';
    $timezone = $this->getSetting('timezone') ?: NULL;
    $langcode = NULL;

    // If an RFC2822 date format is requested, then the month and day have to
    // be in English. @see http://www.faqs.org/rfcs/rfc2822.html
    if ($date_format === 'custom' && ($custom_date_format = $this->getSetting('custom_date_format')) === 'r') {
      $langcode = 'en';
    }

    foreach ($items as $delta => $item) {
      if (!empty($item->imported)) {
        $elements[$delta] = [
          '#cache' => [
            'contexts' => [
              'timezone',
            ],
          ],
          '#markup' => $this->dateFormatter->format($item->get('imported')->getValue(), $date_format, $custom_date_format, $timezone, $langcode),
        ];
      }
    }

    return $elements;
  }

}
