<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

/**
 * Tests feeds_item_guid field formatter.
 *
 * @group feeds
 */
class FeedsItemGuidFormatterTest extends FeedsItemFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set display mode for feeds_item to feeds_item_guid on article content
    // type.
    $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_guid',
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Test the feeds item guid formatter.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemGuidFormatter::viewElements
   *
   * @dataProvider providerGuids
   */
  public function testFeedsItemGuidFormatter($input, $expected) {
    $feed = $this->createCsvFeed();

    // Create an article and set the feeds item guid value.
    $article = $this->createNodeWithFeedsItem($feed);
    $article->get('feeds_item')->getItemByFeed($feed)->guid = $input;

    // Display the article and test we are getting correct output for guid.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay($article->getEntityTypeId(), $article->bundle(), 'default');

    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);
    if ($expected) {
      $this->assertStringContainsString($expected, (string) $rendered_content);
    }
    else {
      // If nothing is expected to be displayed, check if the field is rendered
      // at all.
      $this->assertFeedsItemFieldNotDisplayed($rendered_content, $input);
    }
  }

  /**
   * Data provider for ::testFeedsItemGuidFormatter().
   */
  public function providerGuids() {
    return [
      'integer guid' => ['1', '<div>1</div>'],
      'empty guid' => ['', NULL],
      'zero guid' => ['0', '<div>0</div>'],
      'http url guid' => [
        'http://en.wikipedia.org/wiki/Civilization_(video_game)',
        '<div><a href="http://en.wikipedia.org/wiki/Civilization_(video_game)">http://en.wikipedia.org/wiki/Civilization_(video_game)</a></div>',
      ],
      'https url guid' => [
        'https://en.wikipedia.org/wiki/Duke_Nukem_3D',
        '<div><a href="https://en.wikipedia.org/wiki/Duke_Nukem_3D">https://en.wikipedia.org/wiki/Duke_Nukem_3D</a></div>',
      ],
    ];
  }

}
