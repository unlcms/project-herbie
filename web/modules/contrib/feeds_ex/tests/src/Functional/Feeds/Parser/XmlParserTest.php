<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\XmlParser
 * @group feeds_ex
 */
class XmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'xml';

  /**
   * {@inheritdoc}
   */
  protected $customSourceType = 'xml';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['/items/item'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['!! ', 'Invalid expression'],
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
        'type' => $this->customSourceType,
        'raw' => FALSE,
        'inner' => FALSE,
      ],
    ];
    $custom_source = [
      'label' => 'Name',
      'value' => 'name',
      'machine_name' => 'name',
    ];

    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);

    // Assert that custom sources are displayed.
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/sources');
    $session = $this->assertSession();
    $session->pageTextContains('Custom XML Xpath sources');
    $session->pageTextContains('Name');
    $session->pageTextContains('Raw value');
    $session->pageTextContains('Inner XML');
    // Both options are disabled.
    $session->pageTextNotContains('Enabled');
    $session->pageTextContains('Disabled');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/name');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/name/delete');
  }

  /**
   * Tests adding various XML sources.
   *
   * @dataProvider xmlSourcesDataProvider
   */
  public function testAddXmlSources(array $expected_sources, array $custom_source) {
    $this->setupContext();
    $this->doMappingTest($expected_sources, $custom_source);
  }

  /**
   * Data provider for ::testAddXmlSources().
   */
  public function xmlSourcesDataProvider() {
    return [
      'raw' => [
        'expected_sources' => [
          'name' => [
            'label' => 'Name',
            'value' => 'name',
            'machine_name' => 'name',
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
    ];
  }

  /**
   * Tests that an error is displayed when not providing a label.
   */
  public function testAddXmlSourceFailsWithoutLabel() {
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
  public function testAddXmlSourceFailsWithoutMachineName() {
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
   * Tests editing a XML source.
   */
  public function testEditXmlSource() {
    // Add custom source of type "xml".
    $this->feedType->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'xml',
    ]);
    $this->feedType->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/sources/foo');

    // Change all properties.
    $edit = [
      'source[value]' => 'bar',
      'source[label]' => 'Bar-label',
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
        'type' => 'xml',
        'machine_name' => 'foo',
        'raw' => TRUE,
        'inner' => TRUE,
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests editing a XML source.
   */
  public function testEditXmlSourceFailsWithoutLabel() {
    // Add custom source of type "xml".
    $this->feedType->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'xml',
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
        'type' => 'xml',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests deleting a XML source.
   */
  public function testDeleteXmlSource() {
    // Add custom source of type "xml".
    $this->feedType->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'xml',
    ]);
    $this->feedType->save();
    // Add another custom source of type "xml".
    $this->feedType->addCustomSource('bar', [
      'value' => 'bar',
      'label' => 'Bar-label',
      'type' => 'xml',
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
        'type' => 'xml',
        'machine_name' => 'bar',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

}
