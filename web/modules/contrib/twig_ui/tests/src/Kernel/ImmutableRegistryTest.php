<?php

namespace Drupal\Tests\twig_ui\Kernel;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * Test the immutable registry service.
 *
 * @group twig_ui
 *
 * @coversDefaultClass \Drupal\twig_ui\Theme\ImmutableRegistry
 */
class ImmutableRegistryTest extends KernelTestBase {

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
        'perkins',
      ]);

    $this->container
      ->get('config.factory')
      ->getEditable('system.theme')
      ->set('default', 'grant')
      ->save();

    // Clear the theme registry.
    $this->container
      ->set('twig_ui.immutable_registry', NULL);
  }

  /**
   * Tests ::getTheme().
   *
   * @covers ::getTheme
   */
  public function testGetTheme() {
    $registry = $this->container->get('twig_ui.immutable_registry');
    $registry->get();
    $this->assertEquals($registry->getTheme(), 'grant');
  }

  /**
   * Tests ::setTheme().
   *
   * @covers ::setTheme
   */
  public function testSetTheme() {
    $registry = $this->container->get('twig_ui.immutable_registry');
    $registry->setTheme('perkins');
    $registry->get();
    $this->assertEquals($registry->getTheme(), 'perkins');
  }

  /**
   * Tests ::get().
   *
   * @covers ::get
   */
  public function testGet() {
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

    // Verify 'page' Twig UI template is registered with decorated
    // theme registry.
    $registry = $this->container->get('theme.registry')->get();
    $this->assertEquals($registry['page']['theme path'], PublicStream::basePath() . '/twig_ui/grant');

    // Verify 'page' Twig UI template is not registered with
    // immutable registry.
    $registry = $this->container->get('twig_ui.immutable_registry');
    $registry->setTheme('grant');
    $registry_templates = $registry->get();
    $this->assertEquals($registry_templates['page']['theme path'], 'core/modules/system');

    // Verify immutable registry is cached properly.
    $cache = $this->container->get('cache.default')->get('twig_ui.theme_registry:grant')->data;
    $this->assertEquals($cache['page']['theme path'], 'core/modules/system');
  }

}
