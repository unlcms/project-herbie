<?php

namespace Drupal\feeds;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for the link field item.
 */
interface FeedsItemInterface extends FieldItemInterface {

  /**
   * Gets the URL object.
   *
   * @return \Drupal\Core\Url
   *   Returns a Url object.
   */
  public function getUrl();

}
