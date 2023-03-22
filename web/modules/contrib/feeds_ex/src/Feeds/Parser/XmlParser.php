<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use DOMNode;
use DOMNodeList;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Component\XmlParserTrait;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Utility\XmlUtility;
use Drupal\feeds_ex\XpathDomXpath;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a XML parser using XPath.
 *
 * @FeedsParser(
 *   id = "xml",
 *   title = @Translation("XML"),
 *   description = @Translation("Parse XML with XPath.")
 * )
 */
class XmlParser extends ParserBase implements ContainerFactoryPluginInterface {

  use XmlParserTrait;

  /**
   * The XpathDomXpath object used for parsing.
   *
   * @var \Drupal\feeds_ex\XpathDomXpath
   */
  protected $xpath;

  /**
   * The previous value for XML error handling.
   *
   * @var bool
   */
  protected $handleXmlErrors;

  /**
   * The previous value for the entity loader.
   *
   * @var bool
   */
  protected $entityLoader;

  /**
   * {@inheritdoc}
   */
  protected $encoderClass = '\Drupal\feeds_ex\Encoder\XmlEncoder';

  /**
   * The XML helper class.
   *
   * @var \Drupal\feeds_ex\Utility\XmlUtility
   */
  protected $utility;

  /**
   * Constructs a XmlParser object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\feeds_ex\Utility\XmlUtility $utility
   *   The XML helper class.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, XmlUtility $utility) {
    $this->utility = $utility;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('feeds_ex.xml_utility')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $document = $this->prepareDocument($feed, $fetcher_result);
    $this->xpath = new XpathDomXpath($document);
    $this->sources = $feed->getType()->getCustomSources(['xml']);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $result, StateInterface $state) {
    // Try to free up some memory. There shouldn't be any other references to
    // $this->xpath or the DOMDocument.
    unset($this->xpath);

    // Calculate progress.
    $state->progress($state->total, $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    if (!$state->total) {
      $state->total = $this->xpath->evaluate('count(' . $this->configuration['context']['value'] . ')');
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $this->configuration['line_limit'];

    // A batched XPath expression.
    $context_query = '(' . $this->configuration['context']['value'] . ")[position() > $start and position() <= {$state->pointer}]";
    return $this->xpath->query($context_query);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = $this->xpath->evaluate($expression, $row);

    if (!$result instanceof DOMNodeList) {
      return $result;
    }
    if ($result->length == 0) {
      return;
    }

    $return = [];
    if (!empty($this->sources[$machine_name]['inner'])) {
      foreach ($result as $node) {
        $return[] = $this->getInnerXml($node);
      }
    }
    elseif (!empty($this->sources[$machine_name]['raw'])) {
      foreach ($result as $node) {
        $return[] = $this->getRaw($node);
      }
    }
    else {
      foreach ($result as $node) {
        $return[] = $node->nodeValue;
      }
    }

    // Return a single value if there's only one value.
    return count($return) === 1 ? reset($return) : $return;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_tidy' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigForm() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    if (extension_loaded('tidy')) {
      $form['use_tidy'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use tidy'),
        '#description' => $this->t('The <a href="http://php.net/manual/en/book.tidy.php">Tidy PHP</a> extension has been detected. Select this to clean the markup before parsing.'),
        '#default_value' => $this->configuration['use_tidy'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['xml'];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
    $message = NULL;

    if (!$expression) {
      return $message;
    }

    $this->startErrorHandling();
    $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<items></items>");
    $xml->xpath($expression);

    if ($error = libxml_get_last_error()) {
      // Our variable substitution options can cause syntax errors, check if
      // we're doing that.
      if ($error->code == 1207 && strpos($expression, '$') !== FALSE) {
        // Do nothing.
      }
      // Error code 1219 is an undefined namespace prefix.
      // Our sample doc doesn't have any namespaces.
      elseif ($error->code != 1219) {
        $message = Html::escape(trim($error->message));
      }
    }

    $this->stopErrorHandling();
    return $message;
  }

  /**
   * Prepares the DOM document.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed source.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   *
   * @return DOMDocument
   *   The DOM document.
   */
  protected function prepareDocument(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $raw = $this->prepareRaw($fetcher_result);
    // Remove default namespaces. This has to run after the encoding conversion
    // because a limited set of encodings are supported in regular expressions.
    $raw = $this->removeDefaultNamespaces($raw);

    if ($this->configuration['use_tidy'] && extension_loaded('tidy')) {
      $raw = tidy_repair_string($raw, $this->getTidyConfig(), 'utf8');
    }

    $raw = $this->utility->decodeNamedHtmlEntities($raw);
    return $this->getDomDocument($raw);
  }

  /**
   * Returns the raw XML of a DOM node.
   *
   * @param \DOMNode $node
   *   The node to convert to raw XML.
   *
   * @return string
   *   The raw XML.
   */
  protected function getRaw(DOMNode $node) {
    return $node->ownerDocument->saveXML($node);
  }

  /**
   * Returns the inner XML of a DOM node.
   *
   * @param \DOMNode $node
   *   The node to convert to raw XML.
   *
   * @return string
   *   The inner XML.
   */
  protected function getInnerXml(DOMNode $node) {
    $buffer = '';
    foreach ($node->childNodes as $child) {
      $buffer .= $this->getRaw($child);
    }
    return $buffer;
  }

  /**
   * {@inheritdoc}
   */
  protected function startErrorHandling() {
    parent::startErrorHandling();

    libxml_clear_errors();
    $this->handleXmlErrors = libxml_use_internal_errors(TRUE);

    // Only available in PHP >= 5.2.11 and < PHP 9.0. Since PHP 8.0 it is
    // deprecated. This mitigates a security issue in libxml older than version
    // 2.9.0.
    // See http://symfony.com/blog/security-release-symfony-2-0-17-released for
    // details.
    // @todo remove when Drupal 9 (and thus PHP 7) is no longer supported.
    if (function_exists('libxml_disable_entity_loader') && \PHP_VERSION_ID < 80000) {
      $this->entityLoader = libxml_disable_entity_loader(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function stopErrorHandling() {
    parent::stopErrorHandling();

    libxml_clear_errors();
    libxml_use_internal_errors($this->handleXmlErrors);
    // @todo remove when Drupal 9 (and thus PHP 7) is no longer supported.
    if (function_exists('libxml_disable_entity_loader') && \PHP_VERSION_ID < 80000) {
      libxml_disable_entity_loader($this->entityLoader);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    $return = [];
    foreach (libxml_get_errors() as $error) {

      // Translate error values.
      switch ($error->level) {
        case LIBXML_ERR_FATAL:
          $severity = RfcLogLevel::ERROR;
          break;

        case LIBXML_ERR_ERROR:
          $severity = RfcLogLevel::WARNING;
          break;

        default:
          $severity = RfcLogLevel::NOTICE;
          break;
      }

      $return[] = [
        'message' => '%error on line %num. Error code: %code',
        'variables' => [
          '%error' => trim($error->message),
          '%num' => $error->line,
          '%code' => $error->code,
        ],
        'severity' => $severity,
      ];
    }

    return $return;
  }

  /**
   * Returns the options for phptidy.
   *
   * @see http://php.net/manual/en/book.tidy.php
   * @see tidy_repair_string()
   *
   * @return array
   *   The configuration array.
   */
  protected function getTidyConfig() {
    return [
      'input-xml' => TRUE,
      'output-xml' => TRUE,
      'add-xml-decl' => TRUE,
      'wrap' => 0,
      'tidy-mark' => FALSE,
    ];
  }

}
