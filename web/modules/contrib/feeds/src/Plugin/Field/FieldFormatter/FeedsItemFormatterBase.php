<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;

/**
 * Base class for the feeds item field formatters.
 */
abstract class FeedsItemFormatterBase extends EntityReferenceFormatterBase {

  /**
   * Checks if a value is an url.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is an url. False otherwise.
   */
  public function valueIsUrl($value) {
    $scheme = parse_url($value, PHP_URL_SCHEME);
    if ($scheme === 'http' || $scheme === 'https') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Generates a link element from an Url value.
   *
   * @param \Drupal\Core\Url $url
   *   The Url value you are creating a link with.
   *
   * @return array
   *   A render array.
   */
  public function generateLink(Url $url) {
    return [
      '#type' => 'link',
      '#url' => $url,
      '#title' => $url->toString(),
    ];
  }

}
