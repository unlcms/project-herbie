<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\JmesRuntimeFactory;
use Drupal\feeds_ex\JmesRuntimeFactoryInterface;
use Drupal\feeds_ex\Utility\JsonUtility;
use JmesPath\SyntaxErrorException;

/**
 * Defines a JSON parser using JMESPath.
 *
 * @FeedsParser(
 *   id = "jmespath",
 *   title = @Translation("JSON JMESPath"),
 *   description = @Translation("Parse JSON with JMESPath.")
 * )
 */
class JmesPathParser extends JsonParserBase {

  /**
   * The JMESPath parser.
   *
   * This is an object with an __invoke() method.
   *
   * @var object
   *
   * @todo add interface so checking for an object with an __invoke() method
   * becomes explicit?
   */
  protected $runtime;

  /**
   * A factory to generate JMESPath runtime objects.
   *
   * @var \Drupal\feeds_ex\JmesRuntimeFactoryInterface
   */
  protected $runtimeFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, JsonUtility $utility) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $utility);

    // Set default factory.
    $this->runtimeFactory = new JmesRuntimeFactory();
  }

  /**
   * Sets the factory to use for creating JMESPath Runtime objects.
   *
   * This is useful in unit tests.
   *
   * @param \Drupal\feeds_ex\JmesRuntimeFactoryInterface $factory
   *   The factory to use.
   */
  public function setRuntimeFactory(JmesRuntimeFactoryInterface $factory) {
    $this->runtimeFactory = $factory;
  }

  /**
   * Returns data from the input array that matches a JMESPath expression.
   *
   * @param string $expression
   *   JMESPath expression to evaluate.
   * @param mixed $data
   *   JSON-like data to search.
   *
   * @return mixed|null
   *   Returns the matching data or null.
   */
  protected function search($expression, $data) {
    if (!isset($this->runtime)) {
      $this->runtime = $this->runtimeFactory->createRuntime();
    }

    // Stupid PHP.
    $runtime = $this->runtime;

    return $runtime($expression, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $parsed = $this->utility->decodeJsonObject($this->prepareRaw($fetcher_result));
    $parsed = $this->search($this->configuration['context']['value'], $parsed);

    if (!is_array($parsed) && !is_object($parsed)) {
      throw new \RuntimeException($this->t('The context expression must return an object or array.'));
    }

    // If an object is returned, consider it one item.
    if (is_object($parsed)) {
      return [$parsed];
    }

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
    // @todo Verify if this is necessary. Not sure if the runtime keeps a
    // reference to the input data.
    unset($this->runtime);
    // Calculate progress.
    $state->progress($state->total, $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    try {
      $result = $this->search($expression, $row);
    }
    catch (\Exception $e) {
      // There was an error executing this expression, transform it to a runtime
      // exception, so that it gets properly catched by Feeds.
      throw new \RuntimeException($e->getMessage());
    }

    if (is_object($result)) {
      $result = (array) $result;
    }

    if (!is_array($result)) {
      return $result;
    }

    $count = count($result);

    if ($count === 0) {
      return;
    }

    // Return a single value if there's only one value.
    return count($result) === 1 ? reset($result) : array_values($result);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
    if (!strlen($expression)) {
      return;
    }

    try {
      $this->search($expression, []);
    }
    catch (SyntaxErrorException $e) {
      return $this->formatSyntaxError($e->getMessage());
    }
    catch (\RuntimeException $e) {
      if (strpos($e->getMessage(), 'Argument 0') === 0) {
        // Ignore 'Argument 0 errors'.
        return;
      }

      // In all other cases, rethrow the exception.
      throw $e;
    }
  }

  /**
   * Formats a syntax error message with HTML.
   *
   * A syntax error message can be for example:
   * @code
   * items[].join(`__`,[title,description)
   *                                     ^
   * @endcode
   *
   * @param string $message
   *   The message to format.
   *
   * @return string
   *   The HTML formatted message.
   */
  protected function formatSyntaxError($message) {
    $message = trim($message);
    $message = nl2br($message);
    // Spaces in the error message can be used to point to a specific
    // character in the line above.
    $message = str_replace('  ', '&nbsp;&nbsp;', $message);
    // Remove newlines to make testing easier.
    $message = str_replace("\n", '', $message);

    // Return the message between <pre>-tags so that the error message can be
    // displayed correctly. Else the double spaces may not get displayed.
    return '<pre>' . $message . '</pre>';
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
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!class_exists('JmesPath\AstRuntime')) {
      throw new \RuntimeException($this->t('The JMESPath library is not installed.'));
    }
  }

}
