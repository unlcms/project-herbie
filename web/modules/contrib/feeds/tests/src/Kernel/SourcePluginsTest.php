<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;

/**
 * Tests the behavior of Feeds source plugins.
 *
 * @group feeds
 */
class SourcePluginsTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'feeds_test_extra_sources',
  ];

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpBodyField();

    // Set a site name and slogan.
    $config = \Drupal::service('config.factory')->getEditable('system.site');
    $config->set('name', 'Feeds test site');
    $config->set('slogan', 'It feeds!');
    $config->save();

    // Add two text fields.
    $this->createFieldWithStorage('field_name');
    $this->createFieldWithStorage('field_slogan');
  }

  /**
   * Tests if an entity gets updated when a value in an extra source changes.
   */
  public function testUpdateOnChangeInExtraSource() {
    // Create a feed type.
    $feed_type = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
      // Map the extra sources 'site:name' and 'site:slogan' to 'field_name' and
      // 'field_slogan'.
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_name',
          'map' => ['value' => 'site:name'],
          'settings' => ['format' => 'plain_text'],
        ],
        [
          'target' => 'field_slogan',
          'map' => ['value' => 'site:slogan'],
          'settings' => ['format' => 'plain_text'],
        ],
      ]),
    ]);

    // Create a feed and import a file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Assert that on all 6 nodes, the site name and slogan are set.
    for ($i = 1; $i <= 6; $i++) {
      $node = Node::load($i);
      $this->assertEquals('Feeds test site', $node->field_name->value);
      $this->assertEquals('It feeds!', $node->field_slogan->value);
    }

    // Now update the site slogan and import again.
    $config = \Drupal::service('config.factory')->getEditable('system.site');
    $config->set('slogan', 'Feeds is awesome!');
    $config->save();
    $feed->import();

    // Assert that on all 6 nodes, the slogan is updated (and the site name is
    // not).
    for ($i = 1; $i <= 6; $i++) {
      $node = Node::load($i);
      $this->assertEquals('Feeds test site', $node->field_name->value);
      $this->assertEquals('Feeds is awesome!', $node->field_slogan->value);
    }
  }

  /**
   * Tests if an extra source's value is alterable via the parse event.
   */
  public function testAlterExtraSource() {
    // Create a feed type.
    $feed_type = $this->createFeedType([
      // The module 'feeds_test_extra_sources' alters the data for the feed type
      // 'my_feed'. In there, the title is converted to lower case and the
      // slogan's first word is replaced with the first word of the title.
      'id' => 'my_feed',
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
      // Map the extra sources 'site:name' and 'site:slogan' to 'field_name' and
      // 'field_slogan'.
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_slogan',
          'map' => ['value' => 'site:slogan'],
          'settings' => ['format' => 'plain_text'],
        ],
      ]),
    ]);

    // Create a feed and import a file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $feed->import();

    // Assert that 6 nodes have been created.
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Assert that on all 6 nodes, the titles and slogan have been altered.
    $titles = [
      1 => 'first thoughts: dems\' black tuesday - msnbc.com',
      2 => 'obama wants to fast track a final health care bill - usa today',
      3 => 'why the nexus one makes other android phones obsolete - pc world',
      4 => 'newsmaker-new japan finance minister a fiery battler - reuters',
      5 => 'yemen detains al-qaeda suspects after embassy threats - bloomberg',
      6 => 'egypt, hamas exchange fire on gaza frontier, 1 dead - reuters',
    ];
    $slogans = [
      1 => 'First feeds!',
      2 => 'Obama feeds!',
      3 => 'Why feeds!',
      4 => 'NEWSMAKER-New feeds!',
      5 => 'Yemen feeds!',
      6 => 'Egypt feeds!',
    ];

    for ($i = 1; $i <= 6; $i++) {
      $node = Node::load($i);
      $this->assertEquals($titles[$i], $node->title->value);
      $this->assertEquals($slogans[$i], $node->field_slogan->value);
    }
  }

}
