<?php

namespace Drupal\Tests\twig_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test global settings form.
 *
 * @group twig_ui
 */
class SettingsFormTest extends WebDriverTestBase {

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

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

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests the settings form.
   *
   * @covers \Drupal\twig_ui\Form\SettingsForm
   */
  public function testSettingsForm() {
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/system/twig_ui');

    $this->assertTrue($page->hasCheckedField('edit-allowed-themes-all'));
    $this->assertTrue($page->hasUncheckedField('edit-allowed-themes-selected'));

    $this->assertFalse($page->findById('edit-allowed-theme-list--wrapper')->isVisible());

    $this->assertTrue($page->hasUncheckedField('default_selected_themes[_default]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[stark]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[grant]'));
    $this->assertTrue($page->hasUncheckedField('default_selected_themes[perkins]'));

    $page
      ->findField('allowed_themes')
      ->selectOption('selected');

    $this->assertTrue($page->findById('edit-allowed-theme-list--wrapper')->isVisible());

    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[stark]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[grant]'));
    $this->assertTrue($page->hasUncheckedField('allowed_theme_list[perkins]'));

    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[_default]"]');
    $this->assertTrue($element->isVisible());
    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[stark]"]');
    $this->assertFalse($element->isVisible());
    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[grant]"]');
    $this->assertFalse($element->isVisible());
    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[perkins]"]');
    $this->assertFalse($element->isVisible());

    $page
      ->findField('allowed_theme_list[stark]')
      ->check();

    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[_default]"]');
    $this->assertTrue($element->isVisible());
    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[stark]"]');
    $this->assertTrue($element->isVisible());
    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[grant]"]');
    $this->assertFalse($element->isVisible());
    $element = $page->find('xpath', '//fieldset[@id="edit-default-selected-themes--wrapper"]//input[@name="default_selected_themes[perkins]"]');
    $this->assertFalse($element->isVisible());
  }

  /**
   * Tests CodeMirror integration.
   *
   * @covers \Drupal\twig_ui\Form\SettingsForm
   */
  public function testCodeMirrorIntegration() {
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/system/twig_ui');

    $this->assertSession()->elementNotExists('css', '.form-item-codemirror-config');

    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['codemirror_editor']);

    $this->drupalGet('/admin/config/system/twig_ui');

    $this->assertSession()->elementExists('css', '.form-item-codemirror-config');

    // Populate CodeMirror config field with data.
    $script = 'document.querySelector(".CodeMirror").CodeMirror.setValue("lineNumbers: false\nbuttons:\n  - bold\n  - italic\n");';
    $this->getSession()
      ->evaluateScript($script);

    $page->pressButton('Save');

    // Retrieve CodeMirror config data and verify storage.
    $config = \Drupal::config('twig_ui.settings')->get('codemirror_config');
    $this->assertEquals("lineNumbers: false\nbuttons:\n  - bold\n  - italic\n", $config);
  }

}
