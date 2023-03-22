<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Link
 * @group feeds
 */
class LinkTest extends FeedsKernelTestBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->createFieldWithStorage('field_link', [
      'type' => 'link',
    ]);

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'url' => 'url',
    ]);

    $this->feedType->addMapping([
      'target' => 'field_link',
      'map' => ['uri' => 'url'],
    ]);
    $this->feedType->save();
  }

  /**
   * Tests importing urls.
   */
  public function testImportUrl() {
    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_url.csv',
    ]);
    $feed->import();

    $expected = [
      1 => 'http://example.com',
      2 => 'https://example.com',
      3 => 'internal:/node',
      4 => 'internal:/',
      5 => 'internal:/node',
      6 => 'route:<nolink>',
    ];
    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->field_link->uri);
    }

    // Assert that some entries failed to validate.
    $messages = \Drupal::messenger()->messagesByType('warning');
    $this->assertCount(2, $messages);
    $this->assertStringContainsString('The content <em class="placeholder">Invalid url</em> failed to validate', (string) $messages[0]);
    $this->assertStringContainsString("field_link.0: The path 'string' is invalid.", (string) $messages[0]);
    $this->assertStringContainsString('The content <em class="placeholder">Another invalid url</em> failed to validate', (string) $messages[1]);
    $this->assertStringContainsString('field_link.0.uri: This value should be of the correct primitive type.', (string) $messages[1]);
  }

}
