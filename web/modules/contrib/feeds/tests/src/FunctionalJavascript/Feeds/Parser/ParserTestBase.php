<?php

namespace Drupal\Tests\feeds\FunctionalJavascript\Feeds\Parser;

use Drupal\Tests\feeds\FunctionalJavascript\FeedsJavascriptTestBase;

/**
 * Base class for parser functional javascript tests.
 */
abstract class ParserTestBase extends FeedsJavascriptTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = '';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'parser' => $this->parserId,
      'mappings' => [],
    ]);

    node_add_body_field($this->nodeType);
  }

}
