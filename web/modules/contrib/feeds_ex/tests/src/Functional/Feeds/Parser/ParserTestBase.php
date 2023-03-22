<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

use Drupal\Tests\feeds_ex\Functional\FeedsExBrowserTestBase;

/**
 * Base class for parser functional tests.
 */
abstract class ParserTestBase extends FeedsExBrowserTestBase {

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
   * The custom source type to use.
   *
   * @var string
   */
  protected $customSourceType = 'blank';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'parser' => $this->parserId,
    ]);
  }

  /**
   * Tests basic mapping.
   *
   * @param array $expected_sources
   *   The expected custom sources being set.
   * @param array $custom_source
   *   The properties set on the custom source.
   */
  public function doMappingTest(array $expected_sources, array $custom_source) {
    $this->drupalGet('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping');

    // Set source for title target.
    $edit = [
      'mappings[1][map][value][select]' => 'custom__' . $this->customSourceType,
    ];
    foreach ($custom_source as $key => $value) {
      $edit['mappings[1][map][value][custom__' . $this->customSourceType . '][' . $key . ']'] = $value;
    }
    $this->submitForm($edit, 'Save');

    // Now check the parser configuration.
    $this->feedType = $this->reloadEntity($this->feedType);
    $this->assertEquals($expected_sources, $this->feedType->getCustomSources());
  }

}
