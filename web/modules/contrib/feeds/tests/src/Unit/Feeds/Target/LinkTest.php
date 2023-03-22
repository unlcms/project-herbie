<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Link;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Link
 * @group feeds
 */
class LinkTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'link';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Link::class;
  }

  /**
   * @covers ::prepareValue
   *
   * @param string $expected_uri
   *   The expected uri that is saved.
   * @param string $input_uri
   *   The uri that the source provides.
   *
   * @dataProvider providerUris
   */
  public function testPrepareValue($expected_uri, $input_uri) {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['uri' => $input_uri];
    $method(0, $values);
    $this->assertSame($expected_uri, $values['uri']);
  }

  /**
   * Data provider for ::testPrepareValue().
   */
  public function providerUris() {
    return [
      // Normal uri.
      ['http://example.com', 'http://example.com'],

      // Internal uris.
      ['internal:/node', 'internal:/node'],
      ['internal:/node', '/node'],
      ['internal:/', '<front>'],

      // Entity uris.
      ['entity:node/1', 'entity:node/1'],

      // Linking to nothing.
      ['route:<nolink>', '<nolink>'],
      ['route:<none>', '<none>'],

      // Ignored, rejected by link validation.
      ['node', 'node'],
    ];
  }

}
