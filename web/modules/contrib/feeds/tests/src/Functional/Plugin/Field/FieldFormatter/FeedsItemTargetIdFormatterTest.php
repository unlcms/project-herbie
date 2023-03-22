<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

/**
 * Tests feeds_item_target_id field formatter.
 *
 * @group feeds
 */
class FeedsItemTargetIdFormatterTest extends FeedsItemFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set display mode for feeds_item to feeds_item_target on article content
    // type.
    $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_target_id',
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Tests the feeds target id formatter.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemTargetIdFormatter::viewElements
   *
   * @dataProvider providerTargetIds
   */
  public function testFeedsItemTargetIdFormatter($input, $expected) {
    $feed = $this->createCsvFeed();

    // Create an article with and set the 'target_id' property on the feeds item
    // field.
    $article = $this->createNodeWithFeedsItem($feed);
    $article->get('feeds_item')->getItemByFeed($feed)->target_id = $input;

    // Display the article and test we are getting correct output for target id.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay($article->getEntityTypeId(), $article->bundle(), 'default');

    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);
    if ($expected) {
      $this->assertStringContainsString($expected, (string) $rendered_content);
    }
    else {
      // Make sure no field item is rendered with empty input.
      $this->assertFeedsItemFieldNotDisplayed($rendered_content, $input);
    }
  }

  /**
   * Data provider for ::testFeedsItemTargetIdFormatter().
   */
  public function providerTargetIds() {
    return [
      'empty target id' => ['', NULL],
      'existing target id' => ['1', '<div>1</div>'],
      'non existing target id' => ['123', NULL],
      'weird html string target id' => ['<em>Skeletor!!!!</em>', NULL],
    ];
  }

}
