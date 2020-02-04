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
    // Add trailing slash to all paths if one is not present.
    return rtrim($path, '/') . '/';
  }

}
