<?php

namespace Drupal\Tests\twig_ui\Kernel;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * Tests that Twig UI templates are registered.
 *
 * @group twig_ui
 */
class RegistryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'twig_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installConfig(['twig_ui']);

    $this->container
      ->get('theme_installer')
      ->install([
        'grant',
      ]);
    $this->container
      ->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'grant')
      ->save();

    // Clear the theme registry.
    $this->container
      ->set('theme.registry', NULL);
  }

  /**
   * Tests that Twig UI templates are registered.
   */
  public function testRegistry() {
    $registry = $this->container->get('theme.registry')->get();
    $this->assertSame($registry['page']['theme path'], 'core/modules/system');

    // Create a Twig UI template for node.html.twig and verify registration.
    $this->template = TwigTemplate::create([
      'id' => 'page',
      'label' => 'Page',
      'theme_suggestion' => 'page',
      'template_code' => '<p>Not much of a template</p>',
      'themes' => [
        'grant',
      ],
    ]);
    $this->template->save();

    $registry = $this->container->get('theme.registry')->get();
    $this->assertSame($registry['page']['theme path'], PublicStream::basePath() . '/twig_ui/grant');
  }

}
