<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Exception;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Parser\ParserBase as FeedsParserBase;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Encoder\EncoderInterface;

/**
 * The Feeds extensible parser.
 */
abstract class ParserBase extends FeedsParserBase implements ParserInterface, PluginFormInterface, MappingPluginFormInterface {

  /**
   * The messenger, for compatibility with Drupal 8.5.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $feedsExMessenger;

  /**
   * The class used as the text encoder.
   *
   * @var string
   */
  protected $encoderClass = '\Drupal\feeds_ex\Encoder\TextEncoder';

  /**
   * The encoder used to convert encodings.
   *
   * @var \Drupal\feeds_ex\Encoder\EncoderInterface
   */
  protected $encoder;

  /**
   * The default list of HTML tags allowed by Xss::filter().
   *
   * In addition of \Drupal\Component\Utility\Xss::$htmlTags also the <pre>-tag
   * is added to the list of allowed tags. This is because for the JMESPath
   * parser an error can be generated that needs to be displayed preformatted.
   *
   * @var array
   *
   * @see \Drupal\Component\Utility\Xss::filter()
   */
  protected static $htmlTags = ['a', 'em', 'strong', 'cite', 'blockquote', 'br', 'pre', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd'];

  /**
   * A list of sources to parse.
   *
   * @var array
   */
  protected $sources;

  /**
   * Constructs a ParserBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (!$this->hasConfigForm()) {
      unset($plugin_definition['form']['configuration']);
    }
    $this->sources = [];

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Returns rows to be parsed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   Source information.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result returned by the fetcher.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   *
   * @return array|Traversable
   *   Some iterable that returns rows.
   */
  abstract protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state);

  /**
   * Executes a single source expression.
   *
   * @param string $machine_name
   *   The source machine name being executed.
   * @param string $expression
   *   The expression to execute.
   * @param mixed $row
   *   The row to execute on.
   *
   * @return scalar|[]scalar
   *   Either a scalar, or a list of scalars. If null, the value will be
   *   ignored.
   */
  abstract protected function executeSourceExpression($machine_name, $expression, $row);

  /**
   * Validates an expression.
   *
   * @param string &$expression
   *   The expression to validate.
   *
   * @return string|null
   *   Return the error string, or null if validation was passed.
   */
  abstract protected function validateExpression(&$expression);

  /**
   * Returns the errors after parsing.
   *
   * @return array
   *   A structured array array with keys:
   *   - message: The error message.
   *   - variables: The variables for the message.
   *   - severity: The severity of the message.
   *
   * @see watchdog()
   */
  abstract protected function getErrors();

  /**
   * Allows subclasses to prepare for parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result of the fetching stage.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
  }

  /**
   * Allows subclasses to cleanup after parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\Result\ParserResultInterface $parser_result
   *   The result of parsing.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $parser_result, StateInterface $state) {
  }

  /**
   * Starts internal error handling.
   *
   * Subclasses can override this to being error handling.
   */
  protected function startErrorHandling() {
  }

  /**
   * Stops internal error handling.
   *
   * Subclasses can override this to end error handling.
   */
  protected function stopErrorHandling() {
  }

  /**
   * Loads the necessary library.
   *
   * Subclasses can override this to load the necessary library. It will be
   * called automatically.
   *
   * @throws \RuntimeException
   *   Thrown if the library does not exist.
   */
  protected function loadLibrary() {
  }

  /**
   * Returns whether or not this parser uses a context query.
   *
   * Sub-classes can return false here if they don't require a user-configured
   * context query.
   *
   * @return bool
   *   True if the parser uses a context query and false if not.
   */
  protected function hasConfigurableContext() {
    return TRUE;
  }

  /**
   * Returns the label for single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if the source has a special name. Null otherwise.
   */
  protected function configSourceLabel() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $this->loadLibrary();
    $this->startErrorHandling();
    $result = new ParserResult();
    // @todo Set link?
    // $fetcher_config = $feed->getConfigurationFor($feed->importer->fetcher);
    // $result->link = is_string($fetcher_config['source']) ? $fetcher_config['source'] : '';

    try {
      $this->setUp($feed, $fetcher_result, $state);
      $this->parseItems($feed, $fetcher_result, $result, $state);
      $this->cleanUp($feed, $result, $state);
    }
    catch (EmptyFeedException $e) {
      // The feed is empty.
      $this->getMessenger()->addMessage($this->t('The feed is empty.'), 'warning', FALSE);
    }
    catch (Exception $exception) {
      // Do nothing. Store for later.
    }

    // Display errors.
    $errors = $this->getErrors();
    $this->printErrors($errors, $this->configuration['display_errors'] ? RfcLogLevel::DEBUG : RfcLogLevel::ERROR);

    $this->stopErrorHandling();

    if (isset($exception)) {
      throw $exception;
    }

    return $result;
  }

  /**
   * Performs the actual parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed source.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   * @param \Drupal\feeds\Result\ParserResultInterface $result
   *   The parser result object to populate.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function parseItems(FeedInterface $feed, FetcherResultInterface $fetcher_result, ParserResultInterface $result, StateInterface $state) {
    $expressions = $this->prepareExpressions();
    $variable_map = $this->prepareVariables($expressions);

    foreach ($this->executeContext($feed, $fetcher_result, $state) as $row) {
      if ($item = $this->executeSources($row, $expressions, $variable_map)) {
        $result->addItem($item);
      }
    }
  }

  /**
   * Prepares the expressions for parsing.
   *
   * At this point we just remove empty expressions.
   *
   * @return array
   *   A map of machine name to expression.
   */
  protected function prepareExpressions() {
    $expressions = [];
    foreach ($this->sources as $machine_name => $source) {
      if (strlen($source['value'])) {
        $expressions[$machine_name] = $source['value'];
      }
    }

    return $expressions;
  }

  /**
   * Prepares the variable map used to substitution.
   *
   * @param array $expressions
   *   The expressions being parsed.
   *
   * @return array
   *   A map of machine name to variable name.
   */
  protected function prepareVariables(array $expressions) {
    $variable_map = [];
    foreach ($expressions as $machine_name => $expression) {
      $variable_map[$machine_name] = '$' . $machine_name;
    }
    return $variable_map;
  }

  /**
   * Executes the source expressions.
   *
   * @param mixed $row
   *   A single item returned from the context expression.
   * @param array $expressions
   *   A map of machine name to expression.
   * @param array $variable_map
   *   A map of machine name to varible name.
   *
   * @return array
   *   The fully-parsed item array.
   */
  protected function executeSources($row, array $expressions, array $variable_map) {
    $item = new DynamicItem();
    $variables = [];

    foreach ($expressions as $machine_name => $expression) {
      // Variable substitution.
      $expression = strtr($expression, $variables);

      $result = $this->executeSourceExpression($machine_name, $expression, $row);

      if (!empty($this->sources[$machine_name]['debug'])) {
        $this->debug($result, $machine_name);
      }

      if ($result === NULL) {
        $variables[$variable_map[$machine_name]] = '';
        continue;
      }

      $item->set($machine_name, $result);
      $variables[$variable_map[$machine_name]] = is_array($result) ? reset($result) : $result;
    }

    return $item;
  }

  /**
   * Prints errors to the screen.
   *
   * @param array $errors
   *   A list of errors as returned by stopErrorHandling().
   * @param int $severity
   *   (optional) Limit to only errors of the specified severity. Defaults to
   *   RfcLogLevel::ERROR.
   *
   * @see watchdog()
   */
  protected function printErrors(array $errors, $severity = RfcLogLevel::ERROR) {
    foreach ($errors as $error) {
      if ($error['severity'] > $severity) {
        continue;
      }
      $this->getMessenger()->addMessage($this->t($error['message'], $error['variables']), $error['severity'] <= RfcLogLevel::ERROR ? 'error' : 'warning', FALSE);
    }
  }

  /**
   * Prepares the raw string for parsing.
   *
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   *
   * @return string
   *   The prepared raw string.
   */
  protected function prepareRaw(FetcherResultInterface $fetcher_result) {
    $raw = $this->getEncoder()->convertEncoding($fetcher_result->getRaw());

    // Strip null bytes.
    $raw = trim(str_replace("\0", '', $raw));

    // Check that the string has at least one character.
    if (!isset($raw[0])) {
      throw new EmptyFeedException();
    }

    return $raw;
  }

  /**
   * Renders our debug messages into a list.
   *
   * @param mixed $data
   *   The result of an expression. Either a scalar or a list of scalars.
   * @param string $machine_name
   *   The source key that produced this query.
   */
  protected function debug($data, $machine_name) {
    $name = $machine_name;
    if ($this->sources[$machine_name]['name']) {
      $name = $this->sources[$machine_name]['name'];
    }

    $output = '<strong>' . $name . ':</strong>';
    $data = is_array($data) ? $data : [$data];
    foreach ($data as $key => $value) {
      $data[$key] = Html::escape($value);
    }
    $output .= _theme('item_list', ['items' => $data]);
    $this->getMessenger()->addMessage($output);
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'context' => [
        'value' => '',
      ],
      'display_errors' => FALSE,
      'source_encoding' => ['auto'],
      'line_limit' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Preserve some configuration.
    $config = array_merge([
      'context' => $this->getConfiguration('context'),
    ], $form_state->getValues());

    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state) {
    if ($this->hasConfigurableContext()) {
      $form['context'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Context'),
        '#default_value' => $this->configuration['context']['value'],
        '#description' => $this->t('The base query to run. See the <a href=":link" target="_new">Context query documentation</a> for more information.', [
          ':link' => 'https://www.drupal.org/node/3227985',
        ]),
        '#size' => 50,
        '#required' => TRUE,
        '#maxlength' => 1024,
        '#weight' => -50,
      ];
    }

    parent::mappingFormAlter($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state) {
    try {
      // Validate context.
      if ($this->hasConfigurableContext()) {
        if ($message = $this->validateExpression($form_state->getValue('context'))) {
          $message = new FormattableMarkup(Xss::filter($message, static::$htmlTags), []);
          $form_state->setErrorByName('context', $message);
        }
      }

      // Validate new sources.
      $mappings = $form_state->getValue('mappings');
      if (empty($mappings)) {
        return;
      }
      // Setup a list of select keys we are interested in.
      $select_keys = [];
      foreach ($this->getSupportedCustomSourcePlugins() as $custom_source_plugin_type) {
        $select_keys[] = 'custom__' . $custom_source_plugin_type;
      }
      foreach ($mappings as $i => $mapping) {
        foreach ($mapping['map'] as $subtarget => $map) {
          $select = $map['select'];
          // Check if a new custom source was added of a type we're interested
          // in.
          if (!in_array($select, $select_keys)) {
            // We're not interested in this selected source.
            continue;
          }
          // Check if a value was set for the custom source.
          if (!isset($map[$select]['value'])) {
            // No value was set for the custom source's value.
            continue;
          }
          if ($message = $this->validateExpression($map[$select]['value'])) {
            $message = new FormattableMarkup(Xss::filter($message, static::$htmlTags), []);
            $form_state->setErrorByName("mappings][$i][map][$subtarget][$select][value", $message);
          }
        }
      }
    }
    catch (Exception $e) {
      // Exceptions due to missing libraries could occur, so catch these.
      $form_state->setError($form, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state) {
    $config = [];

    // Set context.
    $config['context'] = [
      'value' => $form_state->getValue('context'),
    ];

    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigForm() {
    return FALSE;
  }

  /**
   * Sets the encoder.
   *
   * @param \Drupal\feeds_ex\Encoder\EncoderInterface $encoder
   *   The encoder.
   *
   * @return $this
   *   The parser object.
   */
  public function setEncoder(EncoderInterface $encoder) {
    $this->encoder = $encoder;
    return $this;
  }

  /**
   * Returns the encoder.
   *
   * @return \Drupal\feeds_ex\Encoder\EncoderInterface
   *   The encoder object.
   */
  public function getEncoder() {
    if (!isset($this->encoder)) {
      $class = $this->encoderClass;
      $this->encoder = new $class($this->configuration['source_encoding']);
    }
    return $this->encoder;
  }

  /**
   * Sets the messenger.
   *
   * For compatibility with both Drupal 8.5 and Drupal 8.6.
   * Basically only useful for automated tests.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function setFeedsExMessenger(MessengerInterface $messenger) {
    if (method_exists($this, 'setMessenger')) {
      $this->setMessenger($messenger);
    }
    else {
      $this->feedsExMessenger = $messenger;
    }
  }

  /**
   * Gets the messenger.
   *
   * For compatibility with both Drupal 8.5 and Drupal 8.6.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger.
   */
  public function getMessenger() {
    if (method_exists($this, 'messenger')) {
      return $this->messenger();
    }
    if (isset($this->feedsExMessenger)) {
      return $this->feedsExMessenger;
    }
    return \Drupal::messenger();
  }

}
