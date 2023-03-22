<?php

namespace Drupal\unl_user\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the unl_user module.
 * @group unl_user
 */
class ImportUserTest extends BrowserTestBase
{

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install
   *
   * @var array
   */
  public static $modules = array('unl_user');

  // A simple user
  private $user;

  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(array(
      'administer users'
    ));
  }

  /**
   * Tests that we can get user data
   */
  public function testImportUser() {
    //We need an admin to log in...
    $this->drupalLogin($this->user);

    //Make sure we can go to the user import form
    $this->drupalGet('/admin/people/import');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, array(
      'search' => 'test'
    ), t('Search'));
    $this->assertText('Records Found. Select a user to import.', 'Able to search for a user');

    $this->drupalPostForm(NULL, array(
      'uid' => 'hhusker1'
    ), t('Import Selected User'));
    $this->assertText('imported hhusker1', 'able to import a user');

    $this->assertTrue((bool) preg_match('/user\/(\d)\/edit/', $this->getUrl()), 'Should take you to the edit page for the new user');
  }
}
