<?php

namespace Drupal\Tests\feeds_ex\Kernel;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\feeds_ex\Traits\FeedsExCommonTrait;

/**
 * Base class for Feeds extensible parser kernel tests.
 */
abstract class FeedsExKernelTestBase extends FeedsKernelTestBase {

  use FeedsExCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'feeds_ex',
    'text',
    'filter',
    'options',
  ];

}
