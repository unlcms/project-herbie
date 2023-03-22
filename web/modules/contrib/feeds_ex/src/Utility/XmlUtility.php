<?php

namespace Drupal\feeds_ex\Utility;

use DOMDocument;
use RuntimeException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Simple XML helpers.
 */
class XmlUtility {

  use StringTranslationTrait;

  /**
   * Creates an HTML document.
   *
   * @param string $source
   *   The string containing the HTML.
   * @param int $options
   *   (optional) Bitwise OR of the libxml option constants. Defaults to 0.
   *
   * @return \DOMDocument
   *   The newly created DOMDocument.
   *
   * @throws \RuntimeException
   *   Thrown if there is a fatal error parsing the XML.
   */
  public function createHtmlDocument($source, $options = 0) {
    // Fun hack to force parsing as utf-8.
    $source = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n" . $source;
    $document = $this->buildDomDocument();

    $options |= LIBXML_NONET;
    $options |= defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0;
    $options |= defined('LIBXML_PARSEHUGE') ? LIBXML_PARSEHUGE : 0;

    $success = $document->loadHTML($source, $options);

    if (!$success) {
      throw new RuntimeException($this->t('There was an error parsing the HTML document.'));
    }
    return $document;
  }

  /**
   * Converts named HTML entities to their UTF-8 equivalent.
   *
   * @param string $markup
   *   The string.
   *
   * @return string
   *   The converted string.
   */
  public function decodeNamedHtmlEntities($markup) {
    $map = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_NOQUOTES|ENT_HTML5, 'UTF-8'));
    unset($map['&amp;'], $map['&lt;'], $map['&gt;']);

    return strtr($markup, $map);
  }

  /**
   * Builds a DOMDocument setting some default values.
   *
   * @return \DOMDocument
   *   A new DOMDocument.
   */
  protected function buildDomDocument() {
    $document = new DOMDocument();
    $document->strictErrorChecking = FALSE;
    $document->resolveExternals = FALSE;
    // Libxml specific.
    $document->substituteEntities = FALSE;
    $document->recover = TRUE;

    return $document;
  }

}
