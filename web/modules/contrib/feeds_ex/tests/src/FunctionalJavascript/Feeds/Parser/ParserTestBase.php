<?php

namespace Drupal\Tests\feeds_ex\FunctionalJavascript\Feeds\Parser;

use Drupal\Tests\feeds\FunctionalJavascript\Feeds\Parser\ParserTestBase as FeedsParserTestBase;
use Drupal\Tests\feeds_ex\Traits\FeedsExCommonTrait;

/**
 * Base class for parser functional javascript tests.
 */
abstract class ParserTestBase extends FeedsParserTestBase {

  use FeedsExCommonTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['feeds_ex'];

}
