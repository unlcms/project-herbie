<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Timestamp
 * @group feeds
 */
class TimestampTest extends FeedsKernelTestBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'created' => 'created',
      'datetime_start' => 'datetime_start',
      'year' => 'year',
    ]);
  }

  /**
   * Tests importing from a timestamp.
   */
  public function testImportTimestamp() {
    $this->feedType->addMapping([
      'target' => 'created',
      'map' => ['value' => 'created'],
    ]);
    $this->feedType->save();

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_date.csv',
    ]);
    $feed->import();

    // Assert three created nodes.
    $this->assertNodeCount(3);

    $expected = [
      1 => 1251936720,
      2 => 1251932360,
      3 => 1190835120,
    ];
    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->created->value);
    }
  }

  /**
   * Tests importing date values with various configurations and formats.
   *
   * @param array $expected
   *   The expected values.
   * @param string $source
   *   The CSV source to use.
   * @param array $settings
   *   The target configuration.
   *
   * @dataProvider withConfigProvider
   */
  public function testWithConfig(array $expected, $source, array $settings = []) {
    $this->feedType->addMapping([
      'target' => 'created',
      'map' => ['value' => $source],
      'settings' => $settings,
    ]);
    $this->feedType->save();

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_date.csv',
    ]);
    $feed->import();

    // Assert three created nodes.
    $this->assertNodeCount(3);

    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->created->value);
    }

    // Assert that the fourth entry failed to validate.
    $messages = \Drupal::messenger()->messagesByType('warning');
    $this->assertCount(1, $messages);
    $this->assertStringContainsString('The content <em class="placeholder">Eodem modo typi</em> failed to validate', (string) $messages[0]);
    $this->assertStringContainsString('created.0.value: This value should be of the correct primitive type.', (string) $messages[0]);
  }

  /**
   * Data provider for ::testWithConfig().
   */
  public function withConfigProvider() {
    $return = [];

    // When the source is already a timestamp, the timezone should not matter.
    $return['ignore-timezone'] = [
      'expected' => [
        1 => 1251936720,
        2 => 1251932360,
        3 => 1190835120,
      ],
      'source' => 'created',
      'settings' => ['timezone' => 'Europe/Amsterdam'],
    ];

    // A 4-digit number should be considered to represent a year (assuming this
    // application becomes obsolete in less than 8000 years).
    $return['year'] = [
      'expected' => [
        1 => -473385600,
        2 => 1420070400,
        3 => 1514764800,
      ],
      'source' => 'year',
      'settings' => ['timezone' => 'UTC'],
    ];
    // Test year value with timezone.
    $return['year-with-timezone'] = [
      'expected' => [
        1 => -473356800,
        2 => 1420099200,
        3 => 1514793600,
      ],
      'source' => 'year',
      'settings' => ['timezone' => 'America/Los_Angeles'],
    ];

    // Los Angeles == UTC-08:00.
    $return['los-angeles'] = [
      'expected' => [
        1 => -446702400,
        2 => 1445495340,
        3 => 1518134400,
      ],
      'source' => 'datetime_start',
      'settings' => ['timezone' => 'America/Los_Angeles'],
    ];
    // Amsterdam == UTC+01:00.
    $return['amsterdam'] = [
      'expected' => [
        1 => -446734800,
        2 => 1445462940,
        3 => 1518134400,
      ],
      'source' => 'datetime_start',
      'settings' => ['timezone' => 'Europe/Amsterdam'],
    ];
    // Sydney == UTC+10:00.
    $return['sydney'] = [
      'expected' => [
        1 => -446767200,
        2 => 1445430540,
        3 => 1518134400,
      ],
      'source' => 'datetime_start',
      'settings' => ['timezone' => 'Australia/Sydney'],
    ];

    return $return;
  }

}
