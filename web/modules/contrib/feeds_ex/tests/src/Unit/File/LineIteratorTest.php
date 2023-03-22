<?php

namespace Drupal\Tests\feeds_ex\Unit\File;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\feeds_ex\Unit\UnitTestBase;
use Drupal\feeds_ex\File\LineIterator;

/**
 * @coversDefaultClass \Drupal\feeds_ex\File\LineIterator
 * @group feeds_ex
 */
class LineIteratorTest extends UnitTestBase {

  /**
   * Tests basic iteration.
   */
  public function test() {
    $iterator = new LineIterator($this->moduleDir . '/tests/resources/test.jsonl');
    $this->assertCount(4, iterator_to_array($iterator));
  }

  /**
   * Tests settings line limits.
   */
  public function testLineLimit() {
    foreach (range(1, 4) as $limit) {
      $iterator = new LineIterator($this->moduleDir . '/tests/resources/test.jsonl');
      $iterator->setLineLimit($limit);
      $array = iterator_to_array($iterator);
      $this->assertCount($limit, $array, new FormattableMarkup('@count lines read.', ['@count' => count($array)]));
    }
  }

  /**
   * Tests resuming file position.
   */
  public function testFileResume() {
    $iterator = new LineIterator($this->moduleDir . '/tests/resources/test.jsonl');
    $iterator->setLineLimit(1);
    foreach (['Gilbert', 'Alexa', 'May', 'Deloise'] as $name) {
      foreach ($iterator as $line) {
        $line = Json::decode($line);
        $this->assertSame($line['name'], $name);
      }
      $iterator->setStartPosition($iterator->ftell());
    }
  }

}
