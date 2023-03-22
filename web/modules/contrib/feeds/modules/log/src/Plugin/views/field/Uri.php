<?php

namespace Drupal\feeds_log\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Url as CoreUrl;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\Url;

/**
 * Handler that turns a file uri into a clickable link.
 *
 * @ViewsField("feeds_log_uri")
 */
class Uri extends Url {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $uri = \Drupal::service('file_url_generator')->generateAbsoluteString($value);
    if (!empty($this->options['display_as_link'])) {
      return Link::fromTextAndUrl($this->sanitizeValue($value), CoreUrl::fromUri($uri))->toString();
    }
    elseif ($value) {
      return $this->sanitizeValue($uri);
    }
  }

}
