<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser
 * @group feeds_ex
 */
class QueryPathXmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'querypathxml';

  /**
   * {@inheritdoc}
   */
  protected $customSourceType = 'querypathxml';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['items item'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['!! ', 'CSS selector is not well formed.'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testMapping() {
    $expected_sources = [
      'name' => [
        'label' => 'Name',
        'value' => 'name',
        'machine_name' => 'name',
        'attribute' => 'attribute-value',
        'type' => $this->customSourceType,
        'raw' => FALSE,
        'inner' => FALSE,
      ],
    ];
    $custom_source = [
      'label' => 'Name',
      'value' => 'name',
      'machine_name' => 'name',
      'attribute' => 'attribute-value',
    ];

    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);

    // Assert that custom sources are displayed.
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/sources');
    $session = $this->assertSession();
    $session->pageTextContains('Custom QueryPath XML sources');
    $session->pageTextContains('Name');
    $session->pageTextContains('Raw value');
    $session->pageTextContains('Inner XML');
    $session->pageTextContains('Attribute');
    $session->pageTextContains('attribute-value');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/name');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/name/delete');
  }

  /**
   * Tests adding various XML sources.
   *
   * @dataProvider queryPathXmlSourcesDataProvider
   */
  public function testAddQueryPathXmlSources(array $expected_sources, array $custom_source) {
    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);
  }

  /**
   * Data provider for ::testAddQueryPathXmlSources().
   */
  public function queryPathXmlSourcesDataProvider() {
    return [
      'raw' => [
        'expected_sources' => [
          'name' => [
            'label' => 'Name',
            'value' => 'name',
            'machine_name' => 'name',
            'attribute' => '',
            'type' => $this->customSourceType,
            'raw' => TRUE,
            'inner' => FALSE,
          ],
        ],
        'custom_source' => [
          'label' => 'Name',
          'value' => 'name',
          'machine_name' => 'name',
          'raw' => TRUE,
          'inner' => FALSE,
        ],
      ],
      'raw+inner' => [
        'expected_sources' => [
          'name' => [
            'label' => 'Name',
            'value' => 'name',
            'machine_name' => 'name',
            'attribute' => '',
            'type' => $this->customSourceType,
            'raw' => TRUE,
            'inner' => TRUE,
          ],
        ],
        'custom_source' => [
          'label' => 'Name',
          'value' => 'name',
          'machine_name' => 'name',
          'raw' => TRUE,
          'inner' => TRUE,
        ],
      ],
      'attribute' => [
        'expected_sources' => [
          'name' => [
            'label' => 'Name',
            'value' => 'name',
            'machine_name' => 'name',
            'attribute' => 'value',
            'type' => $this->customSourceType,
            'raw' => FALSE,
            'inner' => FALSE,
          ],
        ],
        'custom_source' => [
          'label' => 'Name',
          'value' => 'name',
          'machine_name' => 'name',
          'attribute' => 'value',
          'raw' => FALSE,
          'inner' => FALSE,
        ],
      ],
    ];
  }

  /**
   * Tests that an error is displayed when not providing a label.
   */
  public function testAddQueryPathXmlSourceFailsWithoutLabel() {
    $expected_sources = [];
    $custom_source = [
      'value' => 'name',
      'machine_name' => 'name',
    ];

    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);
    $this->assertSession()->pageTextContains('The field Administrative label is required.');
  }

  /**
   * Tests that an error is displayed when not providing a machine name.
   */
  public function testAddQueryPathXmlSourceFailsWithoutMachineName() {
    $expected_sources = [];
    $custom_source = [
      'label' => 'Name',
      'value' => 'name',
    ];

    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);
    $this->assertSession()->pageTextContains('The custom source must have a machine name.');
  }

  /**
   * Tests editing a QueryPath XML source.
   */
  public function testEditQueryPathXmlSource() {
    // Add custom source of type "xml".
    $this->feedType->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'querypathxml',
    ]);
    $this->feedType->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/foo');

    // Change all properties.
    $edit = [
      'source[value]' => 'bar',
      'source[label]' => 'Bar-label',
      'source[attribute]' => 'value',
      'source[raw]' => '1',
      'source[inner]' => '1',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that the custom source changed.
    $feed_type = $this->reloadEntity($this->feedType);
    $expected = [
      'foo' => [
        'value' => 'bar',
        'label' => 'Bar-label',
        'type' => 'querypathxml',
        'machine_name' => 'foo',
        'attribute' => 'value',
        'raw' => TRUE,
        'inner' => TRUE,
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests that editing a QueryPath XML source fails without label.
   */
  public function testEditQueryPathXmlSourceFailsWithoutLabel() {
    // Add custom source of type "xml".
    $this->feedType->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'querypathxml',
    ]);
    $this->feedType->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/foo');

    // Try to empty the source label.
    $edit = [
      'source[label]' => '',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that an error message is displayed.
    $this->assertSession()->pageTextContains('Administrative label field is required.');

    // Assert that the custom source stayed the same.
    $feed_type = $this->reloadEntity($this->feedType);
    $expected = [
      'foo' => [
        'value' => 'foo',
        'label' => 'Foo-label',
        'type' => 'querypathxml',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests deleting a QueryPath XML source.
   */
  public function testDeleteQueryPathXmlSource() {
    // Add custom source of type "xml".
    $this->feedType->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'querypathxml',
    ]);
    $this->feedType->save();
    // Add another custom source of type "xml".
    $this->feedType->addCustomSource('bar', [
      'value' => 'bar',
      'label' => 'Bar-label',
      'type' => 'querypathxml',
    ]);
    $this->feedType->save();

    // Delete the xml source "foo".
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/foo/delete');
    $this->submitForm([], 'Delete');

    // Ensure that only the custom source called "bar" still exists.
    $feed_type = $this->reloadEntity($this->feedType);
    $expected = [
      'bar' => [
        'value' => 'bar',
        'label' => 'Bar-label',
        'type' => 'querypathxml',
        'machine_name' => 'bar',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

}
