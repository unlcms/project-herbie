<?php

namespace Drupal\Tests\unl_utility\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\unl_utility\UNLUtilityTrait;

/**
 * Tests strings methods in UNLUtilityTrait.
 *
 * @group unl_utility
 */
class StringTest extends UnitTestCase {

  /**
   * Tests strings methods in UNLUtilityTrait.
   */
  public function testStringMethods() {
    $this->assertTrue($this->stringStartsWith('Yaba Daba Do', 'Yaba', TRUE));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'yaba', TRUE));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'Do', TRUE));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'chris', TRUE));

    $this->assertTrue($this->stringStartsWith('Yaba Daba Do', 'Yaba'));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'yaba'));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'Do'));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'chris'));

    $this->assertTrue($this->stringStartsWith('Yaba Daba Do', 'Yaba', FALSE));
    $this->assertTrue($this->stringStartsWith('Yaba Daba Do', 'yaba', FALSE));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'Do', FALSE));
    $this->assertFalse($this->stringStartsWith('Yaba Daba Do', 'chris', FALSE));

    $this->assertTrue($this->stringEndsWith('Yaba Daba Do', 'Do', TRUE));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'do', TRUE));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'Yaba', TRUE));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'chris', TRUE));

    $this->assertTrue($this->stringEndsWith('Yaba Daba Do', 'Do'));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'do'));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'Yaba'));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'chris'));

    $this->assertTrue($this->stringEndsWith('Yaba Daba Do', 'Do', FALSE));
    $this->assertTrue($this->stringEndsWith('Yaba Daba Do', 'do', FALSE));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'Yaba', FALSE));
    $this->assertFalse($this->stringEndsWith('Yaba Daba Do', 'chris', FALSE));

    $this->assertTrue($this->stringContains('Yaba Daba Do', 'Do', TRUE));
    $this->assertFalse($this->stringContains('Yaba Daba Do', 'do', TRUE));
    $this->assertTrue($this->stringContains('Yaba Daba Do', 'Yaba', TRUE));
    $this->assertFalse($this->stringContains('Yaba Daba Do', 'chris', TRUE));

    $this->assertTrue($this->stringContains('Yaba Daba Do', 'Do'));
    $this->assertFalse($this->stringContains('Yaba Daba Do', 'do'));
    $this->assertTrue($this->stringContains('Yaba Daba Do', 'Yaba'));
    $this->assertFalse($this->stringContains('Yaba Daba Do', 'chris'));

    $this->assertTrue($this->stringContains('Yaba Daba Do', 'Do', FALSE));
    $this->assertTrue($this->stringContains('Yaba Daba Do', 'do', FALSE));
    $this->assertTrue($this->stringContains('Yaba Daba Do', 'Yaba', FALSE));
    $this->assertFalse($this->stringContains('Yaba Daba Do', 'chris', FALSE));
  }

}
