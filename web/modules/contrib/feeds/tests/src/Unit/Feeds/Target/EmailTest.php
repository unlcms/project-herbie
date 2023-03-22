<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\Feeds\Target\Email;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Email
 * @group feeds
 */
class EmailTest extends FieldTargetTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'email';

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Email::class;
  }

  /**
   * Basic test for the email target.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $target = $this->instantiatePlugin();
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'string'];
    $method(0, $values);
    $this->assertSame($values['value'], '');

    $values = ['value' => 'admin@example.com'];
    $method(0, $values);
    $this->assertSame($values['value'], 'admin@example.com');
  }

}
