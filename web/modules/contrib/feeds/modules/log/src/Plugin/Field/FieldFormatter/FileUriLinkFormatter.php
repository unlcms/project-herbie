<?php

namespace Drupal\feeds_log\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'feeds_log_file_uri_link' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_log_file_uri_link",
 *   label = @Translation("Link to file"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class FileUriLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#url' => Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($item->value)),
          '#title' => $item->value,
          '#attributes' => [
            'target' => '_blank',
          ],
        ];
      }
    }

    return $elements;
  }

}
