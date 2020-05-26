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
    $this->assertTrue(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'Yaba', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'yaba', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'Do', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'chris', TRUE));

    $this->assertTrue(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'Yaba'));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'yaba'));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'Do'));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'chris'));

    $this->assertTrue(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'Yaba', FALSE));
    $this->assertTrue(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'yaba', FALSE));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'Do', FALSE));
    $this->assertFalse(UNLUtilityTrait::stringStartsWith('Yaba Daba Do', 'chris', FALSE));

    $this->assertTrue(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'Do', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'do', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'Yaba', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'chris', TRUE));

    $this->assertTrue(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'Do'));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'do'));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'Yaba'));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'chris'));

    $this->assertTrue(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'Do', FALSE));
    $this->assertTrue(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'do', FALSE));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'Yaba', FALSE));
    $this->assertFalse(UNLUtilityTrait::stringEndsWith('Yaba Daba Do', 'chris', FALSE));

    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'Do', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringContains('Yaba Daba Do', 'do', TRUE));
    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'Yaba', TRUE));
    $this->assertFalse(UNLUtilityTrait::stringContains('Yaba Daba Do', 'chris', TRUE));

    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'Do'));
    $this->assertFalse(UNLUtilityTrait::stringContains('Yaba Daba Do', 'do'));
    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'Yaba'));
    $this->assertFalse(UNLUtilityTrait::stringContains('Yaba Daba Do', 'chris'));

    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'Do', FALSE));
    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'do', FALSE));
    $this->assertTrue(UNLUtilityTrait::stringContains('Yaba Daba Do', 'Yaba', FALSE));
    $this->assertFalse(UNLUtilityTrait::stringContains('Yaba Daba Do', 'chris', FALSE));
  }

}
