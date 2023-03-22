<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test global settings.
 *
 * @group twig_ui
 */
class SettingsTest extends BrowserTestBase {

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
    $this->drupalGet('/admin/config/system/twig_ui');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/system/twig_ui');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests the settings form.
   *
   * @covers \Drupal\twig_ui\Form\SettingsForm
   */
  public function testSettingsForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/system/twig_ui');

    $this->assertTrue($page->hasCheckedField('edit-allowed-themes-all'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[stark]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[grant]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[perkins]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[_default]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[stark]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[grant]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[perkins]'));

    $page
      ->findField('default_selected_themes[_default]')
      ->check();
    $page
      ->findField('default_selected_themes[stark]')
      ->check();
    $page
      ->findField('default_selected_themes[perkins]')
      ->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The configuration options have been saved.');

    $config = \Drupal::config('twig_ui.settings');

    $this->assertEquals('all', $config->get('allowed_themes'));
    $this->assertEmpty($config->get('allowed_theme_list'));
    $this->assertContains('_default', $config->get('default_selected_themes'));
    $this->assertContains('stark', $config->get('default_selected_themes'));
    $this->assertNotContains('grant', $config->get('default_selected_themes'));
    $this->assertContains('perkins', $config->get('default_selected_themes'));

    $this->drupalGet('/admin/config/system/twig_ui');

    $this->assertTrue($page->hasCheckedField('edit-allowed-themes-all'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[stark]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[grant]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[perkins]'));
    $this->assertTrue($page->hasCheckedField('default_selected_themes[_default]'));
    $this->assertTrue($page->hasCheckedField('default_selected_themes[stark]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[grant]'));
    $this->assertTrue($page->hasCheckedField('default_selected_themes[perkins]'));

    $page
      ->findField('allowed_themes')
      ->selectOption('selected');
    $page
      ->findField('allowed_theme_list[perkins]')
      ->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->pageTextContains('The following themes were not saved as "Default themes" because they were not listed on the "Allowed theme list": stark');

    $config = \Drupal::config('twig_ui.settings');

    $this->assertEquals('selected', $config->get('allowed_themes'));
    $this->assertNotContains('stark', $config->get('allowed_theme_list'));
    $this->assertNotContains('grant', $config->get('allowed_theme_list'));
    $this->assertContains('perkins', $config->get('allowed_theme_list'));
    $this->assertContains('_default', $config->get('default_selected_themes'));
    $this->assertNotContains('stark', $config->get('default_selected_themes'));
    $this->assertNotContains('grant', $config->get('default_selected_themes'));
    $this->assertContains('perkins', $config->get('default_selected_themes'));

    $this->drupalGet('/admin/config/system/twig_ui');

    $this->assertTrue($page->hasCheckedField('edit-allowed-themes-selected'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[stark]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[grant]'));
    $this->assertTrue($page->hasCheckedField('allowed_theme_list[perkins]'));
    $this->assertTrue($page->hasCheckedField('default_selected_themes[_default]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[stark]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[grant]'));
    $this->assertTrue($page->hasCheckedField('default_selected_themes[perkins]'));
  }

  /**
   * Tests implementation of default-selected themes setting.
   */
  public function testDefaultSelectedThemesImplementation() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);

    // Verify no default selected themes.
    $this->drupalGet('/admin/structure/templates/add');

    $this->assertTrue($page->hasUncheckedField('themes[stark]'));
    $this->assertTrue($page->hasUncheckedField('themes[grant]'));
    $this->assertTrue($page->hasUncheckedField('themes[perkins]'));

    \Drupal::service('config.factory')
      ->getEditable('twig_ui.settings')
      ->set('default_selected_themes', ['_default', 'grant'])
      ->save();

    // Verify default selected themes from config.
    $this->drupalGet('/admin/structure/templates/add');

    $this->assertTrue($page->hasCheckedField('themes[stark]'));
    $this->assertTrue($page->hasCheckedField('themes[grant]'));
    $this->assertTrue($page->hasUncheckedField('themes[perkins]'));

    // Create a new Twig UI template.
    $page
      ->findField('label')
      ->setValue('Test Template');
    $page
      ->findField('id')
      ->setValue('test_template');
    $page
      ->findField('theme_suggestion')
      ->setValue('node__test');
    $page
      ->findField('template_code')
      ->setValue('{{ content }}' . PHP_EOL . 'Template 1');
    $page
      ->findField('themes[stark]')
      ->uncheck();
    $page
      ->findField('themes[grant]')
      ->uncheck();
    $page
      ->findField('themes[perkins]')
      ->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Test Template Twig template was created.');

    // For existing config entity, verify themes are loaded from config entity
    // config and not from global settings.
    $this->drupalGet('/admin/structure/templates/test_template/edit');

    $this->assertTrue($page->hasUncheckedField('themes[stark]'));
    $this->assertTrue($page->hasUncheckedField('themes[grant]'));
    $this->assertTrue($page->hasCheckedField('themes[perkins]'));
  }

}
