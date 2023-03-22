<?php

namespace Drupal\Tests\twig_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\twig_ui\Traits\HtaccessTestTrait;

/**
 * Tests module requirements.
 *
 * @group twig_ui
 */
class RequirementsTest extends KernelTestBase {

  use HtaccessTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * Tests twig_ui_requirements().
   */
  public function testRequirements() {
    $this->container
      ->get('module_installer')
      ->install([
        'twig_ui',
      ]);

    $template_manager = \Drupal::service('twig_ui.template_manager');
    require_once __DIR__ . '/../../../twig_ui.install';

    // Check requirements after install.
    $requirements = twig_ui_requirements('runtime');
    $this->assertEquals($requirements['twig_ui_templates']['severity'], REQUIREMENT_OK);
    $this->assertEquals($requirements['twig_ui_templates']['value'], 'Twig UI templates directory exists and is protected.');

    // Remove the templates directory and verify requirements error.
    $this->deleteTemplatesDirectory();

    $requirements = twig_ui_requirements('runtime');
    $this->assertEquals($requirements['twig_ui_templates']['severity'], REQUIREMENT_ERROR);
    $this->assertEquals($requirements['twig_ui_templates']['description'], 'The Twig UI templates directory does not exist: ' . $template_manager::DIRECTORY_PATH . '.');

    // Add back templates directory but remove .htaccess and verify
    // requirements error.
    $this->createTemplatesDirectory();
    $this->deleteHtaccessFile();

    $requirements = twig_ui_requirements('runtime');
    $this->assertEquals($requirements['twig_ui_templates']['severity'], REQUIREMENT_ERROR);
    $this->assertEquals($requirements['twig_ui_templates']['description'], 'The Twig UI templates directory is unprotected: ' . $template_manager::DIRECTORY_PATH . '.');
  }

}
