<?php

namespace Drupal\unl_trailing_slash\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TrailingSlashOutboundPathProcessor.
 */
class TrailingSlashOutboundPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = rtrim($path, '/');
    // If the path does not end in a file extension, then add a trailing slash.
    if (!pathinfo($path, PATHINFO_EXTENSION)) {
      $path = $path . '/';
    }
    return $path;
  }

}
