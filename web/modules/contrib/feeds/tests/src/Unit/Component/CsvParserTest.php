<?php

namespace Drupal\Tests\feeds\Unit\Component;

use Drupal\feeds\Component\CsvParser;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\feeds\Component\CsvParser
 * @group feeds
 */
class CsvParserTest extends FeedsUnitTestCase {

  /**
   * Tests parsing a CSV source with several line endings.
   *
   * @dataProvider provider
   */
  public function testAlternateLineEnding(array $expected, $ending) {
    $text = file_get_contents(dirname(dirname(dirname(dirname(__DIR__)))) . '/tests/resources/csv/example.csv');
    $text = str_replace("\r\n", $ending, $text);

    $parser = new \LimitIterator(CsvParser::createFromString($text), 0, 4);

    $first = array_slice($expected, 0, 4);
    $this->assertSame(count(iterator_to_array($parser)), 4);
    $this->assertSame(count(iterator_to_array($parser)), 4);

    foreach ($parser as $delta => $row) {
      $this->assertSame($first[$delta], $row);
    }

    // Test second batch.
    $last_pos = $parser->lastLinePos();

    $parser = (new \LimitIterator(CsvParser::createFromString($text), 0, 4))->setStartByte($last_pos);

    $second = array_slice($expected, 4);

    // // Test that rewinding works as expected.
    $this->assertSame(3, count(iterator_to_array($parser)));
    $this->assertSame(3, count(iterator_to_array($parser)));
    foreach ($parser as $delta => $row) {
      $this->assertSame($second[$delta], $row);
    }
  }

  /**
   * Data provider for testAlternateLineEnding().
   */
  public function provider() {
    $expected = [
      ['Header A', 'Header B', 'Header C'],
      ['"1"', '"2"', '"3"'],
      ['qu"ote', 'qu"ote', 'qu"ote'],
      ["\r\n\r\nline1", "\r\n\r\nline2", "\r\n\r\nline3"],
      ["new\r\nline 1", "new\r\nline 2", "new\r\nline 3"],
      ["\r\n\r\nline1\r\n\r\n", "\r\n\r\nline2\r\n\r\n", "\r\n\r\nline3\r\n\r\n"],
      ['Col A', 'Col B', 'Col, C'],
    ];

    $unix = $expected;
    array_walk_recursive($unix, function (&$item, $key) {
      $item = str_replace("\r\n", "\n", $item);
    });

    return [
      [$expected, "\r\n"],
      [$unix, "\n"],
    ];
  }

  /**
   * @covers ::setHasHeader
   * @covers ::getHeader
   */
  public function testHasHeader() {
    $file = dirname(dirname(dirname(dirname(__DIR__)))) . '/tests/resources/csv/example.csv';
    $parser = CsvParser::createFromFilePath($file)->setHasHeader();

    $this->assertSame(count(iterator_to_array($parser)), 6);
    $this->assertSame(['Header A', 'Header B', 'Header C'], $parser->getHeader());
  }

  /**
   * Tests using an asterisk as delimiter.
   */
  public function testAlternateSeparator() {
    // This implicitly tests lines without a newline.
    $parser = CsvParser::createFromString("a*b*c")
      ->setDelimiter('*');

    $this->assertSame(['a', 'b', 'c'], iterator_to_array($parser)[0]);
  }

  /**
   * Tries to create a CsvParser instance with an invalid file path.
   */
  public function testInvalidFilePath() {
    $this->expectException(InvalidArgumentException::class);
    CsvParser::createFromFilePath('beep boop');
  }

  /**
   * Creates a new CsvParser instance with an invalid CSV source.
   */
  public function testInvalidResourcePath() {
    $this->expectException(InvalidArgumentException::class);
    new CsvParser('beep boop');
  }

  /**
   * Basic test for parsing CSV.
   *
   * @dataProvider csvFileProvider
   */
  public function testCsvParsing($file, $expected) {
    $parser = CsvParser::createFromFilePath($file);
    $parser->setHasHeader();

    $header = $parser->getHeader();

    $output = [];
    $test = [];
    foreach (iterator_to_array($parser) as $row) {
      $new_row = [];
      foreach ($row as $key => $value) {
        if (isset($header[$key])) {
          $new_row[$header[$key]] = $value;
        }
      }
      $output[] = $new_row;
    }

    $this->assertSame($expected, $output);
  }

  /**
   * Data provider for testCsvParsing().
   */
  public function csvFileProvider() {
    $path = dirname(dirname(dirname(dirname(__DIR__)))) . '/tests/resources/csv-parser-component-files';
    $return = [];

    foreach (glob($path . '/csv/*.csv') as $file) {
      $json_file = $path . '/json/' . str_replace('.csv', '.json', basename($file));

      $return[] = [
        $file,
        json_decode(file_get_contents($json_file), TRUE),
      ];
    }

    return $return;
  }

}
