<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

/**
 * Tests feeds_item_target_entity_view field formatter.
 *
 * @group feeds
 */
class FeedsItemTargetEntityFormatterTest extends FeedsItemFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set display mode for feeds_item to feeds_item_target_entity_view on
    // article content type.
    $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_target_entity_view',
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Tests the feeds target entity view formatter.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemTargetEntityFormatter::viewElements
   */
  public function testFeedsItemTargetEntityFormatter() {
    $feed = $this->createCsvFeed();

    // Add a text field to the feed that is displayed on the feed entity in the
    // default view mode.
    $feed = $this->addFieldToFeed($feed);

    // Test the oneliner field we added to the feed is getting rendered along
    // with the feed entity.
    $expected_rendered_oneliner_label = '<div>Witty one liner label</div>';
    $expected_rendered_oneliner_field = 'He is not only from medieval Japan, but also from an alternate universe, so naturally he speaks English!';

    // Create an article with a reference to the feed.
    $article = $this->createNodeWithFeedsItem($feed);

    // Display the article and test we are getting correct output for target
    // feed entity.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay($article->getEntityTypeId(), $article->bundle(), 'default');

    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);

    // Make sure field of the target feed item are rendering as expected.
    $this->assertStringContainsString($expected_rendered_oneliner_label, (string) $rendered_content);
    $this->assertStringContainsString($expected_rendered_oneliner_field, (string) $rendered_content);
  }

}
