<?php

namespace Drupal\Tests\feeds\Unit\Result;

use Drupal\feeds\Result\FetcherResult;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use RuntimeException;

/**
 * @coversDefaultClass \Drupal\feeds\Result\FetcherResult
 * @group feeds
 */
class FetcherResultTest extends FeedsUnitTestCase {

  /**
   * @covers ::getRaw
   */
  public function testGetRaw() {
    file_put_contents('vfs://feeds/test_file', pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult('vfs://feeds/test_file');
    $this->assertSame('I am test data.', $result->getRaw());
  }

  /**
   * @covers ::getFilePath
   */
  public function testGetFilePath() {
    file_put_contents('vfs://feeds/test_file', 'I am test data.');
    $result = new FetcherResult('vfs://feeds/test_file');
    $this->assertSame('vfs://feeds/test_file', $result->getFilePath());
  }

  /**
   * @covers ::getFilePath
   */
  public function testGetSanitizedFilePath() {
    file_put_contents('vfs://feeds/test_file', pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    $result = new FetcherResult('vfs://feeds/test_file');
    $this->assertSame('I am test data.', file_get_contents($result->getFilePath()));
  }

  /**
   * @covers ::getRaw
   */
  public function testNonExistantFile() {
    $result = new FetcherResult('IDONOTEXIST');
    $this->expectException(RuntimeException::class);
    $result->getRaw();
  }

  /**
   * @covers ::getRaw
   */
  public function testNonReadableFile() {
    file_put_contents('vfs://feeds/test_file', 'I am test data.');
    chmod('vfs://feeds/test_file', 000);
    $result = new FetcherResult('vfs://feeds/test_file');
    $this->expectException(RuntimeException::class);
    $result->getRaw();
  }

  /**
   * @covers ::getFilePath
   */
  public function testNonWritableFile() {
    file_put_contents('vfs://feeds/test_file', pack('CCC', 0xef, 0xbb, 0xbf) . 'I am test data.');
    chmod('vfs://feeds/test_file', 0444);
    $result = new FetcherResult('vfs://feeds/test_file');
    $this->expectException(RuntimeException::class);
    $result->getFilePath();
  }

}
