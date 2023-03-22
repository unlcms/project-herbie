<?php

namespace Drupal\feeds_ex\Encoder;

/**
 * Converts the encoding of an HTML document to UTF-8.
 */
class HtmlEncoder extends XmlEncoder {

  /**
   * {@inheritdoc}
   */
  protected $findRegex = '/(<meta[^>]+charset\s*=\s*["\']?)([\w-]+)\b/i';

  /**
   * {@inheritdoc}
   */
  protected $replaceRegex = '/(<meta[^>]+charset\s*=\s*["\']?)([\w-]+)\b/i';

  /**
   * {@inheritdoc}
   */
  protected $replacePattern = '$1UTF-8';

}
