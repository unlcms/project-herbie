<?php

namespace Drupal\feeds_ex_test\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Feeds\Parser\ParserBase;

/**
 * A minimal implementation of a parser for UI testing.
 *
 * @FeedsParser(
 *   id = "feeds_ex_test_ui",
 *   title = @Translation("Test UI parser"),
 *   description = @Translation("Do not use this.")
 * )
 */
class TestUiParser extends ParserBase {

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    return [];
  }

}
