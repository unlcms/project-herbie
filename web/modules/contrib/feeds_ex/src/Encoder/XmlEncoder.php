<?php

namespace Drupal\feeds_ex\Encoder;

/**
 * Converts the encoding of an XML document to UTF-8.
 */
class XmlEncoder extends TextEncoder {

  /**
   * The regex used to find the encoding.
   *
   * @var string
   */
  protected $findRegex = '/^<\?xml[^>]+encoding\s*=\s*("|\')([\w-]+)(\1)/';

  /**
   * The regex used to replace the encoding.
   *
   * @var string
   */
  protected $replaceRegex = '/^(<\?xml[^>]+encoding\s*=\s*("|\'))([\w-]+)(\2)/';

  /**
   * The replacement pattern.
   *
   * @var string
   */
  protected $replacePattern = '$1UTF-8$4';

  /**
   * {@inheritdoc}
   */
  public function convertEncoding($data) {
    // Check for an encoding declaration in the XML prolog.
    $matches = FALSE;
    $encoding = 'ascii';
    if (preg_match($this->findRegex, $data, $matches)) {
      $encoding = $matches[2];
    }
    elseif ($detected = $this->detectEncoding($data)) {
      $encoding = $detected;
    }

    // Unsupported encodings are converted here into UTF-8.
    if (in_array(strtolower($encoding), self::$utf8Compatible)) {
      return $data;
    }

    $data = $this->doConvert($data, $encoding);
    if ($matches) {
      $data = preg_replace($this->replaceRegex, $this->replacePattern, $data);
    }

    return $data;
  }

}
