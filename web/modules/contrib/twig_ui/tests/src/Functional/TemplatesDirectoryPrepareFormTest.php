<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\twig_ui\Traits\HtaccessTestTrait;
use Drupal\twig_ui\TemplateManager;

/**
 * Test the directory prepare form.
 *
 * @group twig_ui
 */
class TemplatesDirectoryPrepareFormTest extends BrowserTestBase {

  use HtaccessTestTrait;

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

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer blocks',
        'administer twig templates',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Test route permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/twig_ui/templates-directory-prepare');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/twig_ui/templates-directory-prepare');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Test TemplatesDirectoryPrepareForm.
   *
   * @covers \Drupal\twig_ui\Form\TemplatesDirectoryPrepareForm
   */
  public function testForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/twig_ui/templates-directory-prepare');

    $assert_session->pageTextContains('Twig UI templates directory exists and is protected.');

    $page->pressButton('Prepare templates directory');

    $assert_session->pageTextContains('The Twig UI templates directory was successfully created and protected.');

    $this->deleteHtaccessFile();
    $this->drupalGet('/admin/twig_ui/templates-directory-prepare');

    $directory_path = TemplateManager::DIRECTORY_PATH;
    $assert_session->pageTextContains('The Twig UI templates directory is unprotected: ' . $directory_path . '.');

    $this->makeUnwritable($directory_path);
    $page->pressButton('Prepare templates directory');

    $assert_session->pageTextContains('Preparation of the Twig UI templates directory resulted in the following error: Unable to create templates directory');

    // Set back to writable so as to not interfere with other tests.
    $this->makeWritable($directory_path);
  }

}
