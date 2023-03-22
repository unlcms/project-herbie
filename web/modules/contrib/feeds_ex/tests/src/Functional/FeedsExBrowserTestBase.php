<?php

namespace Drupal\Tests\feeds_ex\Functional;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use Drupal\Tests\feeds_ex\Traits\FeedsExCommonTrait;

/**
 * Base class for Feeds extensible parser functional tests.
 */
abstract class FeedsExBrowserTestBase extends FeedsBrowserTestBase {

  use FeedsExCommonTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'feeds_ex',
    'node',
    'user',
    'file',
  ];

}
