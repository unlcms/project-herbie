<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use RuntimeException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Flow\JSONPath\JSONPath;
use Flow\JSONPath\JSONPathLexer;

/**
 * Defines a JSON parser using JSONPath.
 *
 * @FeedsParser(
 *   id = "jsonpath",
 *   title = @Translation("JsonPath"),
 *   description = @Translation("Parse JSON with JSONPath.")
 * )
 */
class JsonPathParser extends JsonParserBase {

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $raw = $this->prepareRaw($fetcher_result);
    $parsed = $this->utility->decodeJsonArray($raw);
    $parsed = $this->search($parsed, $this->configuration['context']['value']);

    if (!$state->total) {
      $state->total = count($parsed);
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $this->configuration['line_limit'];
    return array_slice($parsed, $start, $this->configuration['line_limit']);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $result, StateInterface $state) {
    // Calculate progress.
    $state->progress($state->total, $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = $this->search($row, $expression);

    if (is_scalar($result)) {
      return $result;
    }

    // Return a single value if there's only one value.
    return count($result) === 1 ? reset($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);

    // Try to validate if possible.
    if (!class_exists('Flow\JSONPath\JSONPathLexer')) {
      return;
    }

    try {
      $lexer = new JSONPathLexer($expression);
      $lexer->parseExpression();
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    if (!function_exists('json_last_error')) {
      return [];
    }

    if (!$error = json_last_error()) {
      return [];
    }

    $message = [
      'message' => $this->utility->translateError($error),
      'variables' => [],
      'severity' => RfcLogLevel::ERROR,
    ];
    return [$message];
  }

  /**
   * Searches an array via JSONPath.
   *
   * @param array $data
   *   The array to search.
   * @param string $expression
   *   The JSONPath expression.
   *
   * @return mixed
   *   The search results.
   */
  protected function search(array $data, $expression) {
    $json_path = new JSONPath($data);
    return $json_path->find($expression)->getData();
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!class_exists('Flow\JSONPath\JSONPath')) {
      throw new RuntimeException($this->t('The JSONPath library is not installed.'));
    }
  }

}
