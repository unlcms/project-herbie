<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

/**
 * Tests feeds_item_target_id field formatter.
 *
 * @group feeds
 */
class FeedsItemTargetLabelFormatterTest extends FeedsItemFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set display mode for feeds_item to feeds_item_target_label on article
    // content type.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_target_label',
        'settings' => ['link' => FALSE],
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Tests the feeds target label formatter in plain text.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemTargetLabelFormatter::viewElements
   */
  public function testFeedsItemTargetLabelFormatterPlain() {
    $feed = $this->createCsvFeed();
    $feed = $this->addFieldToFeed($feed);

    // Create an article with a reference to the feed.
    $article = $this->createNodeWithFeedsItem($feed);

    // Display the article and test we are getting correct output for label.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay($article->getEntityTypeId(), $article->bundle(), 'default');

    $content = $display->build($article);
    $rendered_content = $this->container->get('renderer')->renderRoot($content);
    $this->htmlOutput($rendered_content);

    // Assert that the label of the feeds_item field is displayed.
    $this->assertStringContainsString('<div>Feeds item</div>', (string) $rendered_content);
    // Assert that the label of the feed is displayed.
    $this->assertStringContainsString('<div>' . $feed->label() . '</div>', (string) $rendered_content);
  }

  /**
   * Tests the feeds target label formatter as a link.
   *
   * @covers \Drupal\feeds\Plugin\Field\FieldFormatter\FeedsItemTargetLabelFormatter::viewElements
   */
  public function testFeedsItemTargetLabelFormatterLink() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Set display mode for feeds_item to feeds_item_target_label on article
    // content type.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'article', 'default')
      ->setComponent('feeds_item', [
        'type' => 'feeds_item_target_label',
        'settings' => ['link' => TRUE],
        'weight' => 1,
      ])
      ->save();

    $feed = $this->createCsvFeed();
    $feed = $this->addFieldToFeed($feed);

    $expected = [
      '#type' => 'link',
      '#title' => $feed->label(),
      '#url' => $feed->toUrl(),
      '#options' => $feed->toUrl()->getOptions(),
      '#cache' => [
        'contexts' => [
          'user.permissions',
        ],
        'tags' => $feed->getCacheTags(),
      ],
    ];

    // Create an article with a reference to the feed.
    $article = $this->createNodeWithFeedsItem($feed);

    // Display the article and test we are getting correct output for label.
    $display = $this->container->get('entity_display.repository')
      ->getViewDisplay($article->getEntityTypeId(), $article->bundle(), 'default');

    $content = $display->build($article);
    $rendered_content = $renderer->renderRoot($content);
    $this->htmlOutput($rendered_content);
    $this->assertStringContainsString('<div>' . (string) $renderer->renderRoot($expected), (string) $rendered_content);
  }

}
