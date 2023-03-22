<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests editing custom sources.
 *
 * @group feeds
 */
class CustomSourceEditFormTest extends FeedsBrowserTestBase {

  /**
   * Tests editing a custom source that does not have a type specified.
   */
  public function testEditCustomSourceWithoutType() {
    // Create a feed type.
    $feed_type = $this->createFeedType();

    // Add custom source of undefined type.
    $feed_type->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
    ]);
    $feed_type->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/sources/foo');

    // Change label and value.
    $edit = [
      'source[value]' => 'bar',
      'source[label]' => 'Bar-label',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that the custom source changed.
    $feed_type = $this->reloadEntity($feed_type);
    $expected = [
      'foo' => [
        'value' => 'bar',
        'label' => 'Bar-label',
        'type' => 'blank',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests editing a CSV source.
   */
  public function testEditCsvSource() {
    // Create a feed type.
    $feed_type = $this->createFeedType();

    // Add custom source of type "csv".
    $feed_type->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'csv',
    ]);
    $feed_type->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/sources/foo');

    // Change label and value.
    $edit = [
      'source[value]' => 'bar',
      'source[label]' => 'Bar-label',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that the custom source changed.
    $feed_type = $this->reloadEntity($feed_type);
    $expected = [
      'foo' => [
        'value' => 'bar',
        'label' => 'Bar-label',
        'type' => 'csv',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests editing a blank source.
   */
  public function testEditBlankSource() {
    // Create a feed type.
    $feed_type = $this->createFeedType();

    // Add custom source of type "blank".
    $feed_type->addCustomSource('foo', [
      'value' => 'foo',
      'label' => 'Foo-label',
      'type' => 'blank',
    ]);
    $feed_type->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/sources/foo');

    // Change label and value.
    $edit = [
      'source[value]' => 'bar',
      'source[label]' => 'Bar-label',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that the custom source changed.
    $feed_type = $this->reloadEntity($feed_type);
    $expected = [
      'foo' => [
        'value' => 'bar',
        'label' => 'Bar-label',
        'type' => 'blank',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests editing a custom source of type "Foo".
   *
   * This custom source type is provided by the test module "Feeds test plugin"
   * and only available when using the parser "Parser with Foo Source".
   */
  public function testEditFooSource() {
    // Enable the test module "Feeds test plugin" that provides a custom source
    // with the properties "propbool" and "proptext".
    $this->container->get('module_installer')->install(['feeds_test_plugin']);

    // Create a feed type with the parser "Parser with Foo Source", because for
    // that parser the custom source type "Foo" is available.
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_foo_source',
    ]);

    // Add custom source of type "foo" without the extra properties.
    $feed_type->addCustomSource('foo', [
      'value' => 'qux',
      'label' => 'Qux-label',
      'type' => 'foo',
    ]);
    $feed_type->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/sources/foo');

    // Change the extra properties.
    $edit = [
      'source[propbool]' => '1',
      'source[proptext]' => 'Bar',
    ];
    $this->submitForm($edit, 'Save');

    // Flush all caches and reload the feed type. Flushing all caches is needed
    // because else the testbot doesn't somehow reload the feed type from the
    // database.
    drupal_flush_all_caches();
    $feed_type = $this->reloadEntity($feed_type);

    // Assert that the custom source changed.
    $expected = [
      'foo' => [
        'value' => 'qux',
        'label' => 'Qux-label',
        'type' => 'foo',
        'propbool' => TRUE,
        'proptext' => 'Bar',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests that validation handlers on custom sources are ran.
   */
  public function testEditSourceValidation() {
    // Enable the test module "Feeds test plugin" that provides a custom source
    // with the properties "propbool" and "proptext".
    $this->container->get('module_installer')->install(['feeds_test_plugin']);

    // Create a feed type with the parser "Parser with Foo Source", because for
    // that parser the custom source type "Foo" is available.
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_foo_source',
    ]);

    // Add custom source of type "foo" without the extra properties.
    $feed_type->addCustomSource('foo', [
      'value' => 'qux',
      'label' => 'Qux-label',
      'type' => 'foo',
    ]);
    $feed_type->save();

    // Go to the custom source edit form.
    $this->drupalGet('admin/structure/feeds/manage/' . $feed_type->id() . '/sources/foo');

    // Set the proptext field to a value that triggers a validation error.
    $edit = [
      'source[proptext]' => 'Illegal value',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that an error message is displayed.
    $this->assertSession()->pageTextContains('The textfield contains "Illegal value".');

    // Flush all caches and reload the feed type. Flushing all caches is needed
    // because else the testbot doesn't somehow reload the feed type from the
    // database.
    drupal_flush_all_caches();
    $feed_type = $this->reloadEntity($feed_type);

    // Assert that the custom source stayed the same.
    $expected = [
      'foo' => [
        'value' => 'qux',
        'label' => 'Qux-label',
        'type' => 'foo',
        'machine_name' => 'foo',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

}
