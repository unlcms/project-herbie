<?php

namespace Drupal\Tests\feeds_ex\Unit\Utility;

use Drupal\Tests\feeds_ex\Unit\UnitTestBase;
use Drupal\feeds_ex\Utility\XmlUtility;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Utility\XmlUtility
 * @group feeds_ex
 */
class XmlUtilityTest extends UnitTestBase {

  /**
   * @covers ::decodeNamedHtmlEntities
   */
  public function testDecodeNamedHtmlEntities() {
    $xml = '<root>&Atilde;&amp;&lt;&gt;</root>';
    $utility = new XmlUtility();
    $xml = $utility->decodeNamedHtmlEntities($xml);
    $this->assertSame('<root>Ã&amp;&lt;&gt;</root>', $xml);
  }

}
