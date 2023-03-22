<?php

namespace Drupal\Tests\layout_builder_styles\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
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
class LayoutBuilderStyleRestrictionsTest extends BrowserTestBase {

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
   * Layout Restrictions apply.
   */
  public function testLayoutRestrictions() {
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
      'create and edit custom blocks',
    ]));

    LayoutBuilderStyleGroup::create([
      'id' => 'group',
      'label' => 'group',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE_SELECT,
      'required' => FALSE,
    ])->save();

    // Create an unrestricted layout style.
    LayoutBuilderStyle::create([
      'id' => 'unrestricted',
      'label' => 'Unrestricted',
      'classes' => 'unrestricted-class',
      'type' => 'section',
      'group' => 'group',
    ])->save();

    // Restrict the 2nd layout style to 'layout_onecol'.
    LayoutBuilderStyle::create([
      'id' => 'onecol_only',
      'label' => 'Onecol only',
      'classes' => 'onecol-only',
      'type' => 'section',
      'group' => 'group',
      'layout_restrictions' => ['layout_onecol'],
    ])->save();

    // Restrict the 3rd layout style to 'layout_twocol'.
    LayoutBuilderStyle::create([
      'id' => 'twocol_only',
      'label' => 'Twocol only',
      'classes' => 'twocol-only',
      'type' => 'section',
      'group' => 'group',
      'layout_restrictions' => ['layout_twocol_section'],
    ])->save();

    // Examine which styles are allowed on onecol layout.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('One column');
    // One column can use "Unrestricted" and "Onecol only".
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="onecol_only"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="twocol_only"]');

    // Examine which styles are allowed on twocol layout.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('Two column');
    // Two column can use "Unrestricted" and "Twocol only".
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="onecol_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="twocol_only"]');

    // Examine which styles are allowed on three column layout.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add section');
    $page->clickLink('Three column');
    // Three column can only use "Unrestricted".
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="onecol_only"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="twocol_only"]');
  }

  /**
   * Block type restrictions should apply to inline & reusable blocks.
   */
  public function testBlockRestrictions() {
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
      'create and edit custom blocks',
    ]));

    // Create 2 custom block types, with block instances.
    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
    ]);
    $bundle->save();
    $bundle = BlockContentType::create([
      'id' => 'alternate',
      'label' => 'Alternate',
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());
    $blocks = [
      'Basic Block 1' => 'basic',
      'Alternate Block 1' => 'alternate',
    ];
    foreach ($blocks as $info => $type) {
      $block = BlockContent::create([
        'info' => $info,
        'type' => $type,
        'body' => [
          [
            'value' => 'This is the block content',
            'format' => filter_default_format(),
          ],
        ],
      ]);
      $block->save();
      $blocks[$info] = $block->uuid();
    }

    LayoutBuilderStyleGroup::create([
      'id' => 'group',
      'label' => 'group',
      'multiselect' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE,
      'form_type' => LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE_SELECT,
      'required' => FALSE,
    ])->save();

    // Create block styles for blocks.
    LayoutBuilderStyle::create([
      'id' => 'unrestricted',
      'label' => 'Unrestricted',
      'classes' => 'foo-style-class bar-style-class',
      'type' => 'component',
      'group' => 'group',
    ])->save();

    // Restrict the 2nd block style to 'basic' blocks.
    LayoutBuilderStyle::create([
      'id' => 'basic_only',
      'label' => 'Basic only',
      'classes' => 'foo2-style-class bar2-style-class',
      'type' => 'component',
      'group' => 'group',
      'block_restrictions' => ['inline_block:basic'],
    ])->save();

    // Restrict the 3rd block style to only the 'Promoted to frontpage' block.
    LayoutBuilderStyle::create([
      'id' => 'promoted_only',
      'label' => 'Promoted only',
      'classes' => 'foo3-style-class bar3-style-class',
      'type' => 'component',
      'group' => 'group',
      'block_restrictions' => ['field_block:node:bundle_with_section_field:promote'],
    ])->save();

    // Restrict the 4th block style to 'alternate' or 'promoted'.
    LayoutBuilderStyle::create([
      'id' => 'multi_allow',
      'label' => 'Alternate and promoted',
      'classes' => 'foo4-style-class bar4-style-class',
      'type' => 'component',
      'group' => 'group',
      'block_restrictions' => [
        'inline_block:alternate',
        'field_block:node:bundle_with_section_field:promote',
      ],
    ])->save();

    // Block instances are not allowed to be restricted.
    $this->drupalGet('admin/config/content/layout_builder_style/unrestricted/edit');
    foreach (array_values($blocks) as $uuid) {
      $assert_session->elementNotExists('css', 'input[name="block_restrictions[block_content:' . $uuid . ']"]');
    }

    // Examine which styles are allowed on basic block type.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Basic Block 1');
    // Basic block can use "Unrestricted" and "Basic only".
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="basic_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="promoted_only"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="multi_allow"]');

    // Examine which styles are allowed on alternate block type.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Alternate Block 1');
    // Alternate block can use "Unrestricted" and "Alternate only".
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="basic_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="promoted_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="multi_allow"]');

    // Examine which styles are allowed on 'Promoted to front page'.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Promoted to front page');
    // Promoted gets "Unrestricted", "Alternate and promoted", & "Promoted".
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="basic_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="promoted_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="multi_allow"]');

    // Examine which styles are allowed on inline basic block.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Create custom block');
    $page->clickLink('Basic');
    // Basic block can use "Unrestricted" and "Basic only".
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="basic_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="promoted_only"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="multi_allow"]');

    // Examine which styles are allowed on inline alternate block.
    $this->drupalGet($block_node->toUrl());
    $page->clickLink('Layout');
    $page->clickLink('Add block');
    $page->clickLink('Create custom block');
    $page->clickLink('Alternate');
    // Alternate block can use "Unrestricted" and "Alternate only".
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="basic_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="unrestricted"]');
    $assert_session->elementNotExists('css', 'select#edit-layout-builder-style-group option[value="promoted_only"]');
    $assert_session->elementExists('css', 'select#edit-layout-builder-style-group option[value="multi_allow"]');
  }

}
