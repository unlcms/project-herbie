<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JsonPathParser
 * @group feeds_ex
 */
class JsonPathParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'jsonpath';

  /**
   * {@inheritdoc}
   */
  protected $customSourceType = 'json';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['$.items.*'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['.hello*', 'Unable to parse token hello* in expression: .hello*'],
      ['!!', 'Unable to parse token !! in expression: .!!'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testMapping() {
    $expected_sources = [
      'name' => [
        'label' => 'name',
        'value' => 'name',
        'machine_name' => 'name',
        'type' => $this->customSourceType,
      ],
    ];
    $custom_source = [
      'value' => 'name',
      'machine_name' => 'name',
    ];

    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);
  }

  /**
   * Tests mapping validation.
   */
  public function testInvalidMappingSource() {
    $this->drupalGet('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping');

    // First, set context.
    $edit = [
      'context' => '$.items.*',
    ];

    $this->submitForm($edit, 'Save');

    // Now setup bad mapping.
    $edit = [
      'mappings[1][map][value][select]' => 'custom__json',
      'mappings[1][map][value][custom__json][value]' => '.hello*',
      'mappings[1][map][value][custom__json][machine_name]' => '_hello_',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that a warning is displayed.
    $this->assertSession()->pageTextContains('Unable to parse token hello* in expression: .hello*');

    // Now check the parser configuration.
    $this->feedType = $this->reloadEntity($this->feedType);
    $this->assertEquals([], $this->feedType->getCustomSources());
  }

}
