<?php

namespace Drupal\Tests\feeds\Functional\Plugin\Field\FieldFormatter;

use Drupal\feeds\FeedInterface;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Base class for the feeds item field formatter tests.
 *
 * @group feeds
 */
abstract class FeedsItemFormatterTestBase extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create feeds_item field.
    $this->createFieldWithStorage('feeds_item', [
      'type' => 'feeds_item',
      'label' => 'Feeds item',
    ]);
  }

  /**
   * Creates a feed type and feed using the CSV parser.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The created feed, with a CSV source already set.
   */
  protected function createCsvFeed() {
    $feed_type = $this->createFeedTypeForCsv(['guid', 'title'], [
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'feeds_item',
          'map' => [
            'guid' => 'guid',
            'url' => 'url',
          ],
        ],
      ]),
    ]);

    // Create a feed for the article to belong to.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);

    return $feed;
  }

  /**
   * Asserts that the feeds_item field is not displayed.
   *
   * Potentially the Stark theme can output a feeds_item field as follows:
   * @code
   * <div>
   *   <div>Feeds item</div>
   *   <div>05/11/2020 - 15:19</div>
   * </div>
   * @endcode
   *
   * @param string $rendered_content
   *   The rendered content.
   * @param string $input
   *   A property value from the feeds_item field.
   */
  protected function assertFeedsItemFieldNotDisplayed($rendered_content, $input) {
    $this->assertStringNotContainsString('<div>Feeds item</div>', (string) $rendered_content);
    $this->assertStringNotContainsString('<div>' . $input . '</div>', (string) $rendered_content);
  }

  /**
   * Creates a field for the feed item and set its value.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to add a field to.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The updated feed entity.
   */
  public function addFieldToFeed(FeedInterface $feed) {
    $feed_type_id = $feed->getType()->id();

    $this->createFieldWithStorage('oneliner', [
      'entity_type' => 'feeds_feed',
      'bundle' => $feed_type_id,
      'type' => 'text',
      'label' => 'Witty one liner label',
    ]);

    $this->container->get('entity_display.repository')
      ->getViewDisplay('feeds_feed', $feed_type_id, 'default')
      ->setComponent('oneliner', [
        'type' => 'text_default',
        'settings' => ['label' => 'Witty one liner'],
      ])
      ->save();

    $feed = $this->reloadEntity($feed);
    $feed->oneliner = [
      'value' => 'He is not only from medieval Japan, but also from an alternate universe, so naturally he speaks English!',
      'format' => 'plain_text',
    ];
    $feed->save();

    return $feed;
  }

}
