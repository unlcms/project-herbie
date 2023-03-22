<?php

namespace Drupal\Tests\layout_builder_styles\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;
use Drupal\layout_builder_styles\Entity\LayoutBuilderStyle;
use Drupal\layout_builder_styles\Entity\LayoutBuilderStyleGroup;
use Drupal\layout_builder_styles\LayoutBuilderStyleGroupInterface;

/**
 * Tests the Layout Builder Styles apply as expected.
 *
 * @group layout_builder_styles
 */
class LayoutBuilderStyleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'block_content',
    'node',
    'layout_builder_styles',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create two nodes.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);

    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Test Layout Builder section styles can be created and applied.
   */
  public function testSectionStyles() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $section_node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'manage layout builder styles',
    ]));

    LayoutBuilderStyleGroup::create([
      'id' => 'group',
      'label' => 'Group',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE_SELECT,
      'required' => FALSE,
    ])->save();

    // Create styles for section.
    LayoutBuilderStyle::create([
      'id' => 'Foobar',
      'label' => 'Foobar',
      'classes' => 'foo-style-class bar-style-class',
      'type' => 'section',
      'group' => 'group',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'Foobar2',
      'label' => 'Foobar2',
      'classes' => 'foo2-style-class bar2-style-class',
      'type' => 'section',
      'group' => 'group',
    ])->save();

    // Add section to node with new styles.
    $this->drupalGet('node/' . $section_node->id());
    $assert_session->responseNotContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('Two column');
    // Verify that only a single option may be selected.
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option');
    $page->selectFieldOption('edit-layout-builder-style-group', 'Foobar');
    $page->pressButton('Add section');

    // Confirm section element contains the proper classes.
    $page->pressButton('Save layout');
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
  }

  /**
   * Test Layout Builder block styles can be created and applied.
   */
  public function testBlockStyles() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $block_node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'manage layout builder styles',
    ]));

    LayoutBuilderStyleGroup::create([
      'id' => 'group',
      'label' => 'Group',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_SINGLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_CHECKBOXES,
      'required' => FALSE,
    ])->save();

    // Create styles for blocks.
    LayoutBuilderStyle::create([
      'id' => 'Foobar',
      'label' => 'Foobar',
      'classes' => 'foo-style-class bar-style-class',
      'type' => 'component',
      'group' => 'group',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'Foobar2',
      'label' => 'Foobar2',
      'classes' => 'foo2-style-class bar2-style-class',
      'type' => 'component',
      'group' => 'group',
    ])->save();

    // Add block to node with new style.
    $this->drupalGet('node/' . $block_node->id());
    $assert_session->responseNotContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    // Verify that only a single option may be selected.
    $assert_session->elementExists('css', '[name=layout_builder_style_group]');
    $page->selectFieldOption('layout_builder_style_group', 'Foobar');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm block element contains proper classes.
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
  }

}
