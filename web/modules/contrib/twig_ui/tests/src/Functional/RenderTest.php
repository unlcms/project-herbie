<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\Entity\Node;
use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * Test that Twig UI template files are rendered.
 *
 * @group twig_ui
 */
class RenderTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'twig_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'grant';

  /**
   * Tests that Twig UI template files are rendered.
   */
  public function testRender() {
    $this->createContentType([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomString(),
    ]);
    $node->save();

    // Verify default node.html.twig file is loaded from theme.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Grant theme node.html.twig');

    // Create a Twig UI template for node.html.twig and verify render.
    $this->template = TwigTemplate::create([
      'id' => 'node',
      'label' => 'Node',
      'theme_suggestion' => 'node',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 1',
      'themes' => [
        'grant',
      ],
    ]);
    $this->template->save();

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Test template 1');

    // Create a Twig UI template for node--page.html.twig and verify render.
    $this->template = TwigTemplate::create([
      'id' => 'node_page',
      'label' => 'Node - Page',
      'theme_suggestion' => 'node__page',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 2',
      'themes' => [
        'grant',
      ],
    ]);
    $this->template->save();

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Test template 2');

    // Disable the 'node' Twig UI template and verify no render.
    $this->template->disable();
    $this->template->save();

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains('Test template 2');
  }

}
