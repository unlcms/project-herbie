<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use QueryPath\DOMQuery;

/**
 * Defines a HTML parser using QueryPath.
 *
 * @todo Make convertEncoding() into a helper function so that they aren't \
 *   copied in 2 places.
 *
 * @FeedsParser(
 *   id = "querypathhtml",
 *   title = @Translation("QueryPath HTML"),
 *   description = @Translation("Parse HTML with QueryPath.")
 * )
 */
class QueryPathHtmlParser extends QueryPathXmlParser {

  /**
   * {@inheritdoc}
   */
  protected $encoderClass = '\Drupal\feeds_ex\Encoder\HtmlEncoder';

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Change some parser settings.
    $this->queryPathOptions['use_parser'] = 'html';
    $this->sources = $feed->getType()->getCustomSources(['querypathxml']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRawValue(DOMQuery $node) {
    return $node->html();
  }

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
  protected function getTidyConfig() {
    return [
      'merge-divs' => FALSE,
      'merge-spans' => FALSE,
      'join-styles' => FALSE,
      'drop-empty-paras' => FALSE,
      'wrap' => 0,
      'tidy-mark' => FALSE,
      'escape-cdata' => TRUE,
      'word-2000' => TRUE,
    ];
  }

}
