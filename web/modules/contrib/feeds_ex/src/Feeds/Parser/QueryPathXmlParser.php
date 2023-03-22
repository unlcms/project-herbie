<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use QueryPath\QueryPath;
use QueryPath\DOMQuery;
use QueryPath\CSS\ParseException;

/**
 * Defines a XML parser using QueryPath.
 *
 * @FeedsParser(
 *   id = "querypathxml",
 *   title = @Translation("QueryPath XML"),
 *   description = @Translation("Parse XML with QueryPath.")
 * )
 */
class QueryPathXmlParser extends XmlParser {

  /**
   * Options passed to QueryPath.
   *
   * @var array
   */
  protected $queryPathOptions = [
    'ignore_parser_warnings' => TRUE,
    'use_parser' => 'xml',
    'strip_low_ascii' => FALSE,
    'replace_entities' => FALSE,
    'omit_xml_declaration' => TRUE,
    'encoding' => 'UTF-8',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    parent::setUp($feed, $fetcher_result, $state);
    $this->sources = $feed->getType()->getCustomSources(['querypathxml']);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $document = $this->prepareDocument($feed, $fetcher_result);
    $query_path = QueryPath::with($document, $this->configuration['context']['value'], $this->queryPathOptions);

    if (!$state->total) {
      $state->total = $query_path->size();
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $this->configuration['line_limit'];
    $state->progress($state->total, $state->pointer);

    return $query_path->slice($start, $this->configuration['line_limit']);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = QueryPath::with($row, $expression, $this->queryPathOptions);

    if ($result->size() == 0) {
      return;
    }

    $config = $this->sources[$machine_name] + [
      'attribute' => '',
    ];

    $return = [];

    if (strlen($config['attribute'])) {
      foreach ($result as $node) {
        $return[] = $node->attr($config['attribute']);
      }
    }
    elseif (!empty($config['inner'])) {
      foreach ($result as $node) {
        $return[] = $node->innerXML();
      }
    }
    elseif (!empty($config['raw'])) {
      foreach ($result as $node) {
        $return[] = $this->getRawValue($node);
      }
    }
    else {
      foreach ($result as $node) {
        $return[] = $node->text();
      }
    }

    // Return a single value if there's only one value.
    return count($return) === 1 ? reset($return) : $return;
  }

  /**
   * Returns the raw value.
   *
   * @param \QueryPath\DOMQuery $node
   *   The DOMQuery object to return a raw value for.
   *
   * @return string
   *   A raw string value.
   */
  protected function getRawValue(DOMQuery $node) {
    return $node->xml();
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $this->loadLibrary();
    $expression = trim($expression);
    if (!$expression) {
      return;
    }
    try {
      $parser = QueryPath::with(NULL, $expression);
    }
    catch (ParseException $e) {
      return new HtmlEscapedText($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['querypathxml'];
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!class_exists(QueryPath::class)) {
      throw new \RuntimeException($this->t('The QueryPath library is not installed.'));
    }
  }

}
