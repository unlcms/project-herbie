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
class LayoutBuilderStyleGroupTest extends BrowserTestBase {

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
   * Test that section groups (with styles) can be created and applied.
   */
  public function testSectionStyleGroups() {
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
      'manage layout builder style groups',
    ]));

    LayoutBuilderStyleGroup::create([
      'label' => 'Test Multiple',
      'id' => 'testmulti',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE_SELECT,
      'required' => FALSE,
    ])->save();

    LayoutBuilderStyleGroup::create([
      'label' => 'Test Required',
      'id' => 'testrequired',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE_SELECT,
      'required' => TRUE,
    ])->save();

    LayoutBuilderStyleGroup::create([
      'label' => 'Test Single',
      'id' => 'testsingle',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_SINGLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_CHECKBOXES,
      'required' => FALSE,
    ])->save();

    LayoutBuilderStyleGroup::create([
      'label' => 'Test Empty',
      'id' => 'testempty',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_SINGLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_CHECKBOXES,
      'required' => FALSE,
    ])->save();

    // Create styles for section.
    LayoutBuilderStyle::create([
      'id' => 'foobar',
      'label' => 'Foobar',
      'classes' => 'foo-style-class bar-style-class',
      'type' => 'section',
      'group' => 'testmulti',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'foobar2',
      'label' => 'Foobar2',
      'classes' => 'foo2-style-class bar2-style-class',
      'type' => 'section',
      'group' => 'testmulti',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'foobar3',
      'label' => 'Foobar3',
      'classes' => 'foo3-style-class bar3-style-class',
      'type' => 'section',
      'group' => 'testsingle',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'foobar4',
      'label' => 'Foobar4',
      'classes' => 'foo4-style-class bar4-style-class',
      'type' => 'section',
      'group' => 'testsingle',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'foobar5',
      'label' => 'Foobar5',
      'classes' => 'foo5-style-class',
      'type' => 'section',
      'group' => 'testrequired',
    ])->save();

    LayoutBuilderStyle::create([
      'id' => 'foobar6',
      'label' => 'Foobar6',
      'classes' => 'foo6-style-class',
      'type' => 'section',
      'group' => 'testrequired',
    ])->save();

    // Confirm we cannot delete a group that has styles associated with it.
    $this->drupalGet('/admin/config/content/layout_builder_style/group/testsingle/delete');
    $assert_session->responseContains('You may not remove this group');
    $this->drupalGet('/admin/config/content/layout_builder_style/group/testempty/delete');
    $assert_session->responseNotContains('You may not remove this group');

    // Confirm form validation catches if required group is not selected.
    $this->drupalGet('node/' . $section_node->id());
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('Two column');
    // Verify 'Required' group exists.
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-testrequired option');
    // Do not make a selection.
    $page->pressButton('Add section');
    $assert_session->responseContains('Test Required field is required.');

    // Add section to node with new styles.
    $this->drupalGet('node/' . $section_node->id());
    $assert_session->responseNotContains('foo-style-class bar-style-class');
    $assert_session->responseNotContains('foo2-style-class bar2-style-class');
    $assert_session->responseNotContains('foo3-style-class bar3-style-class');
    $assert_session->responseNotContains('foo4-style-class bar4-style-class');
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('Two column');
    // Verify groups multiple select.
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-testmulti option');
    $page->selectFieldOption('edit-layout-builder-style-testmulti', 'foobar', TRUE);
    $page->selectFieldOption('edit-layout-builder-style-testmulti', 'foobar2', TRUE);
    $page->selectFieldOption('edit-layout-builder-style-testrequired', 'foobar5', TRUE);
    $page->pressButton('Add section');

    // Confirm section element contains the proper classes.
    $page->pressButton('Save layout');
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseContains('foo2-style-class bar2-style-class');
    $assert_session->responseNotContains('foo3-style-class bar3-style-class');
    $assert_session->responseNotContains('foo4-style-class bar4-style-class');

    $this->drupalGet('layout_builder/configure/section/overrides/node.' . $section_node->id() . '/0');

    // Finding a single checkbox, or a radio item.
    $page->selectFieldOption('layout_builder_style_testsingle', 'foobar3');
    $page->pressButton('Update');
    $assert_session->responseContains('foo-style-class bar-style-class');
    $assert_session->responseContains('foo2-style-class bar2-style-class');
    $assert_session->responseContains('foo3-style-class bar3-style-class');
    $assert_session->responseNotContains('foo4-style-class bar4-style-class');
  }

}
