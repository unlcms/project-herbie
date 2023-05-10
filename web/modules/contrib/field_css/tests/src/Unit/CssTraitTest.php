<?php

namespace Drupal\Tests\field_css\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\field_css\Traits\CssTrait;

/**
 * Tests CssTrait methods.
 *
 * @group field_css
 */
class CssTraitTest extends UnitTestCase {

  use CssTrait;

  /**
   * Tests CssTrait->addSelectorPrefix().
   *
   * Both the selector prefixing and the CSS formatting returned from
   * OutputFormat::createPretty() are verified.
   */
  public function testPrefix() {
    $prefix = '.test-prefix';
    $css_code = 'p { color: green; }' . PHP_EOL . '#id-selector .class-selector { margin: 1em; }';
    $expected_return = PHP_EOL . '.test-prefix p {' . PHP_EOL . '	color: green;' . PHP_EOL . '}' . PHP_EOL . PHP_EOL . '.test-prefix #id-selector .class-selector {' . PHP_EOL . '	margin: 1em;' . PHP_EOL . '}' . PHP_EOL;
    $actual_return = $this->addSelectorPrefix($css_code, $prefix);
    $this->assertSame($expected_return, $actual_return);

    // Verify CSS code is processed by OutputFormat::createPretty().
    // Extra space between "color:" and "green" is stripped
    // by OutputFormat::createPretty().
    $css_code = 'p { color:  green; }' . PHP_EOL . '#id-selector .class-selector { margin: 1em; }';
    $expected_return = PHP_EOL . '.test-prefix p {' . PHP_EOL . '	color: green;' . PHP_EOL . '}' . PHP_EOL . PHP_EOL . '.test-prefix #id-selector .class-selector {' . PHP_EOL . '	margin: 1em;' . PHP_EOL . '}' . PHP_EOL;
    $actual_return = $this->addSelectorPrefix($css_code, $prefix);
    $this->assertSame($expected_return, $actual_return);
  }

  /**
   * Tests CssTrait->formatCss().
   *
   * Verify CSS code is processed by OutputFormat::createPretty().
   */
  public function testFormatCss() {
    // Extra space between "color:" and "green" is stripped
    // by OutputFormat::createPretty().
    $css_code = 'p { color:  green; }' . PHP_EOL . '#id-selector .class-selector { margin: 1em; }';
    $expected_return = PHP_EOL . 'p {' . PHP_EOL . '	color: green;' . PHP_EOL . '}' . PHP_EOL . PHP_EOL . '#id-selector .class-selector {' . PHP_EOL . '	margin: 1em;' . PHP_EOL . '}' . PHP_EOL;
    $actual_return = $this->formatCss($css_code);
    $this->assertSame($expected_return, $actual_return);
  }

}
