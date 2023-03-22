<?php

namespace Drupal\Tests\feeds\Functional\Controller;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Tests the custom source list controller.
 *
 * @group feeds
 */
class CustomSourceListControllerTest extends FeedsBrowserTestBase {

  /**
   * Tests displaying a list of custom sources.
   */
  public function testListCustomSources() {
    // Create a CSV feed type with a few CSV sources and a few sources that
    // don't specify a type.
    $feed_type = $this->createFeedTypeForCsv([]);

    // Add a custom source of an undefined type.
    $feed_type->addCustomSource('_foo', [
      'value' => 'foo.title',
      'label' => 'Foo-label',
    ]);
    // Add a custom source of type "csv".
    $feed_type->addCustomSource('_bar', [
      'value' => 'bar.title',
      'label' => 'Bar-label',
      'type' => 'csv',
    ]);
    // Add another custom source of type "csv".
    $feed_type->addCustomSource('_qux', [
      'value' => 'qux.title',
      'label' => 'Qux-label',
      'type' => 'csv',
    ]);
    $feed_type->save();

    // Go to the custom source listing page.
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources');

    // Assert that the expected custom sources are displayed, along with edit
    // and delete links.
    $session = $this->assertSession();
    $session->pageTextContains('Custom Blank sources');
    $session->pageTextContains('Foo-label');
    $session->pageTextContains('foo.title');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_foo');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_foo/delete');
    $session->pageTextContains('Custom CSV column sources');
    $session->pageTextContains('Bar-label');
    $session->pageTextContains('bar.title');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_bar');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_bar/delete');
    $session->pageTextContains('Qux-label');
    $session->pageTextContains('qux.title');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_qux');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_qux/delete');
  }

  /**
   * Tests displaying custom source listing when there are none.
   */
  public function testDisplayEmptyList() {
    // Create a CSV feed type without any custom sources defined.
    $feed_type = $this->createFeedTypeForCsv([], [
      'mappings' => [],
    ]);

    // Go to the custom source listing page.
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources');

    // Assert that no custom sources are displayed and there is instead a
    // message.
    $this->assertSession()->pageTextContains('There are no custom sources yet.');
  }

  /**
   * Tests if extra columns can get displayed.
   *
   * Some custom source types have extra properties. The value of these extra
   * properties should in some cases be displayed on the overview.
   */
  public function testWithAdditionalColumns() {
    // Enable the test module "Feeds test plugin" that provides a custom source
    // with the properties "propbool" and "proptext".
    $this->container->get('module_installer')->install(['feeds_test_plugin']);

    // Create a feed type with the parser "Parser with Foo Source", because for
    // that parser the custom source type "Foo" is available.
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_foo_source',
    ]);

    // Add a custom source for this parser.
    $feed_type->addCustomSource('_foo', [
      'value' => 'foo.title',
      'label' => 'Foo-label',
      'type' => 'foo',
      'propbool' => TRUE,
      'proptext' => 'Footsie',
    ]);
    // Add another custom source for this parser.
    $feed_type->addCustomSource('_qux', [
      'value' => 'qux.title',
      'label' => 'Qux-label',
      'type' => 'foo',
      'propbool' => FALSE,
      'proptext' => 'Quxawitz',
    ]);
    // Also add a custom source of an undefined type.
    $feed_type->addCustomSource('_bar', [
      'value' => 'bar.title',
      'label' => 'Bar-label',
    ]);
    $feed_type->save();

    // Go to the custom source listing page.
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources');

    // Assert that the extra columns are displayed with their values.
    $session = $this->assertSession();
    $session->pageTextContains('Custom Foo sources');
    $session->pageTextContains('Foo-label');
    $session->pageTextContains('foo.title');
    $session->pageTextContains('Boolean value');
    $session->pageTextContains('Enabled');
    $session->pageTextContains('Text value');
    $session->pageTextContains('Footsie');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_foo');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_foo/delete');
    $session->pageTextContains('Qux-label');
    $session->pageTextContains('qux.title');
    $session->pageTextContains('Disabled');
    $session->pageTextContains('Quxawitz');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_qux');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_qux/delete');
    $session->pageTextContains('Custom Blank sources');
    $session->pageTextContains('Bar-label');
    $session->pageTextContains('bar.title');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_bar');
    $session->linkByHrefExists('/admin/structure/feeds/manage/' . $feed_type->id() . '/sources/_bar/delete');
  }

}
