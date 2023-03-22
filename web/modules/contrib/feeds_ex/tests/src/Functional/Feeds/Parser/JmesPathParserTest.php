<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JmesPathParser
 * @group feeds_ex
 */
class JmesPathParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'jmespath';

  /**
   * {@inheritdoc}
   */
  protected $customSourceType = 'json';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['items'],
      ['length(people)'],
      ['sort_by(people, &age)'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['!! ', 'Syntax error at character'],
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
      'context' => '@',
    ];
    $this->submitForm($edit, 'Save');

    // Now try to configure an invalid mapping source.
    $edit = [
      'mappings[1][map][value][select]' => 'custom__json',
      // Invalid source expression. Closing bracket is missing.
      'mappings[1][map][value][custom__json][value]' => 'items[].join(`__`,[title,description)',
      'mappings[1][map][value][custom__json][machine_name]' => 'title_desc',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that a warning is displayed.
    $this->assertSession()->pageTextContains('Syntax error at character');

    // Now check the parser configuration.
    $this->feedType = $this->reloadEntity($this->feedType);
    $this->assertEquals([], $this->feedType->getCustomSources());
  }

  /**
   * Tests an import with an invalid source expression.
   */
  public function testImportWithInvalidExpression() {
    // Add body field.
    node_add_body_field($this->nodeType);

    // Create a feed type with an invalid jmespath source value.
    $feed_type = $this->createFeedType([
      'parser' => 'jmespath',
      'parser_configuration' => [
        'context' => [
          'value' => '@',
        ],
      ],
      'custom_sources' => [
        'title' => [
          'label' => 'Title',
          'value' => 'items[].title',
          'machine_name' => 'title',
          'type' => 'json',
        ],
        'title_desc' => [
          'label' => 'Title and description',
          // Invalid source expression. Closing bracket is missing.
          'value' => 'items[].join(`__`,[title,description)',
          'machine_name' => 'title_desc',
          'type' => 'json',
        ],
      ],
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
          'unique' => ['value' => TRUE],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'title_desc'],
          'settings' => [
            'format' => 'plain_text',
          ],
        ],
      ],
    ]);

    // And try to do a batch import.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/test.json',
    ]);
    $this->batchImport($feed);

    // And assert that it failed gracefully.
    $this->assertSession()->pageTextContains('There are no new Article items.');
    $this->assertSession()->pageTextContains('Syntax error at character');
  }

}
