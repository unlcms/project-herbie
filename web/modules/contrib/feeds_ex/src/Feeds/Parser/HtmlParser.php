<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use DOMNode;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;

/**
 * Defines a HTML parser using XPath.
 *
 * @FeedsParser(
 *   id = "html",
 *   title = @Translation("HTML"),
 *   description = @Translation("Parse HTML with XPath.")
 * )
 */
class HtmlParser extends XmlParser {

  /**
   * Whether this version of PHP has the correct saveHTML() method.
   *
   * @var bool
   */
  protected $useSaveHTML;

  /**
   * {@inheritdoc}
   */
  protected $encoderClass = '\Drupal\feeds_ex\Encoder\HtmlEncoder';

  /**
   * {@inheritdoc}
   */
  protected function prepareDocument(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $raw = $this->prepareRaw($fetcher_result);
    if ($this->configuration['use_tidy'] && extension_loaded('tidy')) {
      $raw = tidy_repair_string($raw, $this->getTidyConfig(), 'utf8');
    }
    return $this->utility->createHtmlDocument($raw);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRaw(DOMNode $node) {
    return $node->ownerDocument->saveHTML($node);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTidyConfig() {
    return [
      'merge-divs' => FALSE,
      'merge-spans' => FALSE,
      'join-styles' => FALSE,
      'drop-empty-paras' => FALSE,
      'wrap' => 0,
      'tidy-mark' => FALSE,
      'escape-cdata' => TRUE,
    ];
  }

}
