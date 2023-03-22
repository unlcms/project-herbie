<?php

namespace Drupal\Tests\twig_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\twig_ui\Entity\TwigTemplate;
use Drupal\twig_ui\TemplateManager;
use Drupal\Tests\twig_ui\Traits\HtaccessTestTrait;

/**
 * Tests install/uninstall functions.
 *
 * @group twig_ui
 */
class InstallTest extends KernelTestBase {

  use HtaccessTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * Test that requirements are verified at install.
   */
  public function testInstallation() {
    // Use logger to check for install errors until
    // https://www.drupal.org/project/drupal/issues/2862282 provides
    // a solution.
    $this->container
      ->get('module_installer')
      ->install([
        'dblog',
      ]);

    // Purposefully cause installation to throw error when attempting to create
    // the templates directory.
    $this->makeUnwritable('public://');

    $this->container
      ->get('module_installer')
      ->install([
        'twig_ui',
      ]);

    // Get error from watchdog.
    $query = \Drupal::database()->select('watchdog', 'w')->fields('w');
    $query->condition('w.type', 'twig_ui', '=');
    $query->condition('w.message', 'Preparation of the Twig UI templates directory resulted in the following error: @message', '=');
    $results = $query->execute()->fetchAll();

    $this->assertEquals($results[0]->message, 'Preparation of the Twig UI templates directory resulted in the following error: @message');
  }

  /**
   * Test that requirements are verified at uninstall.
   */
  public function testUninstallation() {
    $this->container
      ->get('module_installer')
      ->install([
        'twig_ui',
      ]);
    $directory = TemplateManager::DIRECTORY_PATH;

    $this->container
      ->get('module_installer')
      ->uninstall([
        'twig_ui',
      ]);

    $this->assertDirectoryNotExists($directory);
  }

  /**
   * Tests twig_ui_update_8101().
   */
  public function testUpdate8101() {
    $this->container
      ->get('module_installer')
      ->install([
        'twig_ui',
      ]);

    // Create a disabled Twig UI template.
    $template = TwigTemplate::create([
      'status' => FALSE,
      'id' => 'page',
      'label' => 'Page',
      'theme_suggestion' => 'page',
      'template_code' => '<p>Not much of a template</p>',
      'themes' => [
        'grant',
      ],
    ]);
    $template->save();

    $this->assertTrue($template->status() == FALSE);

    // Run update function.
    twig_ui_update_8101();

    // Load Twig UI template and verify status changed to enabled.
    $template = \Drupal::service('entity_type.manager')
      ->getStorage('twig_template')
      ->loadByProperties(['id' => 'page']);
    $template = array_shift($template);

    $this->assertTrue($template->status() == TRUE);
  }

  /**
   * Tests twig_ui_update_8102().
   */
  public function testUpdate8102() {
    $this->container
      ->get('module_installer')
      ->install([
        'twig_ui',
      ]);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface */
    $config_factory = \Drupal::service('config.factory');
    $config = $config_factory->getEditable('twig_ui.settings');
    $data = $config->get();

    $data['default_selected_themes'] = ['grant', 'perkins'];
    $config->setData($data);
    $config->save();

    // Run update function.
    twig_ui_update_8102();

    $config = $config_factory->getEditable('twig_ui.settings');
    $data = $config->get();

    $this->assertEquals($data['allowed_themes'], 'all');
    $this->assertEquals($data['allowed_theme_list'], []);
    $this->assertEquals($data['default_selected_themes'], ['grant', 'perkins']);
  }

}
