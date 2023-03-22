<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the TemplateLoadAjaxController controller/routes.
 *
 * @group twig_ui
 *
 * @coversDefaultClass \Drupal\twig_ui\Controller\TemplateLoadAjaxController
 */
class TemplateLoadAjaxControllerTest extends BrowserTestBase {

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test non-administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'twig_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setup() : void {
    parent::setup();

    \Drupal::service('theme_installer')->install(['grant']);
    \Drupal::service('theme_installer')->install(['perkins']);

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer blocks',
        'administer twig templates',
        'administer twig ui templates settings',
        'load twig templates from file system',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer twig templates',
      ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Test route permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/ajax/twig-ui/template-list-load/grant');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/ajax/twig-ui/template-load/grant/block');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/ajax/twig-ui/template-list-load/grant');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/ajax/twig-ui/template-load/grant/block');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests ::templates().
   *
   * @covers ::templates
   */
  public function testTemplates() {
    $this->drupalLogin($this->adminUser);

    $registry_templates = $this->drupalGet('/ajax/twig-ui/template-list-load/grant');
    $this->assertJson($registry_templates);
    $registry_templates = json_decode($registry_templates);
    // 'block' template should be available since Block module is enabled.
    $this->assertTrue(in_array('block', $registry_templates));
  }

  /**
   * Tests ::templateCode().
   *
   * @covers ::templateCode
   */
  public function testTemplateCode() {
    $this->drupalLogin($this->adminUser);

    $controller_return = $this->drupalGet('/ajax/twig-ui/template-load/grant/block');
    $this->assertJson($controller_return);
    $controller_return = json_decode($controller_return);

    $extension_path_resolver = \Drupal::service('extension.path.resolver');
    $block_module_path = $extension_path_resolver->getPath('module', 'block');
    $template_path = $block_module_path . '/templates/block.html.twig';
    $abs_template_path = \Drupal::service('file_system')->realpath($template_path);
    $template_code = file_get_contents($abs_template_path);
    $this->assertEquals($controller_return->raw_code, $template_code);
    $this->assertEquals($controller_return->escaped_code, htmlentities($template_code));
    $this->assertEquals($controller_return->file_path, $template_path);
  }

}
