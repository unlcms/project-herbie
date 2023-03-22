<?php

namespace Drupal\feeds_ex;

use DOMDocument;
use DOMNode;
use DOMXPath;

/**
 * Wraps DOMXPath simplifying usage.
 */
class XpathDomXpath {

  /**
   * The XPath query object.
   *
   * @var \DOMXPath
   */
  protected $xpath;

  /**
   * Constructs a XpathDomXpath object.
   *
   * @param \DOMDocument $document
   *   The DOM document to parse.
   *
   * @todo Add an option to force a deep scan of namespaces.
   */
  public function __construct(DOMDocument $document) {
    $this->xpath = new DOMXPath($document);

    // Find all namespaces.
    // Calling simplexml_import_dom() and SimpleXML::getNamespaces() is several
    // orders of magnitude faster than searching for the namespaces ourselves
    // using XPath.
    $simple = simplexml_import_dom($document);
    // An empty DOMDocument will make $simple NULL.
    if ($simple === NULL) {
      return;
    }
    foreach ($simple->getNamespaces(TRUE) as $prefix => $namespace) {
      $this->xpath->registerNamespace($prefix, $namespace);
    }
  }

  /**
   * Evaluates the XPath expression and returns a typed result if possible.
   *
   * @param string $expression
   *   The XPath expression to execute.
   * @param \DOMNode $context_node
   *   (optional) The optional contextnode can be specified for doing relative
   *   XPath queries. Defaults to the root element.
   *
   * @see DOMXPath::evaluate()
   */
  public function evaluate($expression, DOMNode $context_node = NULL) {
    if ($context_node === NULL) {
      $context_node = $this->xpath->document;
    }
    return $this->xpath->evaluate($expression, $context_node);
  }

  /**
   * Evaluates the given XPath expression.
   *
   * @param string $expression
   *   The XPath expression to execute.
   * @param \DOMNode $context_node
   *   (optional) The optional contextnode can be specified for doing relative
   *   XPath queries. Defaults to the root element.
   *
   * @see DOMXPath::query()
   */
  public function query($expression, DOMNode $context_node = NULL) {
    if ($context_node === NULL) {
      $context_node = $this->xpath->document;
    }
    return $this->xpath->query($expression, $context_node);
  }

}
