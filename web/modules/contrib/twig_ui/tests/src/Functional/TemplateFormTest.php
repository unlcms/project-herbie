<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * Test the template form.
 *
 * @group twig_ui
 */
class TemplateFormTest extends BrowserTestBase {

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
  }

  /**
   * Test route permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/structure/templates/add');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/templates/node/edit');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/templates/node/delete');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/templates/node/clone');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/templates/add');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/structure/templates/node/edit');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/structure/templates/node/delete');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/structure/templates/node/clone');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Test TwigTemplateForm.
   *
   * @covers \Drupal\twig_ui\Entity\TwigTemplateForm
   */
  public function testForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $twig_template_storage = $template = \Drupal::service('entity_type.manager')
      ->getStorage('twig_template');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/templates/add');

    $assert_session->elementExists('css', '.field-template-code');

    // Fill out form and attempt to use an existing machine name.
    $page
      ->findField('label')
      ->setValue('Test Template');
    $page
      ->findField('id')
      ->setValue('node');
    $page
      ->findField('theme_suggestion')
      ->setValue('node__test');
    $page
      ->findField('template_code')
      ->setValue('{{ content }}' . PHP_EOL . 'Template 1');
    $page
      ->findField('themes[grant]')
      ->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Try again with an unused machine name but an already registered
    // theme suggestion.
    $page
      ->findField('id')
      ->setValue('test_template');
    $page
      ->findField('theme_suggestion')
      ->setValue('node');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The node theme suggestion is already registered for the Grant theme by the Node Twig UI template. It must be disabled or deleted in order for this Twig UI template to be enabled.');

    // Try again with an unregistered theme suggestion.
    $page
      ->findField('theme_suggestion')
      ->setValue('node__test');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Test Template Twig template was created.');

    $this->drupalGet('/admin/structure/templates/test_template/edit');

    // Update the form.
    $page
      ->findField('template_code')
      ->setValue('{{ content }}' . PHP_EOL . 'Template 1a');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Test Template Twig template was updated.');

    // Verify data is stored correctly.
    $template = $twig_template_storage->loadByProperties(['id' => 'test_template']);
    $template = array_shift($template);

    $this->assertSame($template->get('label'), 'Test Template');
    $this->assertSame($template->get('theme_suggestion'), 'node__test');
    $this->assertSame($template->get('template_code'), '{{ content }}' . PHP_EOL . 'Template 1a');
    $this->assertSame($template->get('themes'), ['grant']);
    $this->assertSame($template->get('status'), TRUE);

    // Disable the 'node' Twig UI template and verify the status message.
    $this->drupalGet('/admin/structure/templates/' . $this->template->id() . '/edit');

    $page
      ->findField('status')
      ->uncheck();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Node Twig template was disabled.');

    // With the 'node' Twig UI template disabled, verify another Twig UI
    // template can be registered with the same theme suggestion and theme.
    $this->drupalGet('/admin/structure/templates/add');

    $page
      ->findField('label')
      ->setValue('Node Duplicate');
    $page
      ->findField('id')
      ->setValue('node_duplicate');
    $page
      ->findField('theme_suggestion')
      ->setValue('node');
    $page
      ->findField('template_code')
      ->setValue('{{ content }}' . PHP_EOL . 'Template Node Duplicate');
    $page
      ->findField('themes[grant]')
      ->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Node Duplicate Twig template was created.');

    // Edit disabled 'node' Twig UI template and attempt to enable.
    // Verify error message re existing active Twig UI template.
    $this->drupalGet('/admin/structure/templates/node/edit');

    $page
      ->findField('status')
      ->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The node theme suggestion is already registered for the Grant theme by the Node Duplicate Twig UI template. It must be disabled or deleted in order for this Twig UI template to be enabled.');

    // Create a Twig UI template that is initially disabled and verify
    // status message.
    $this->drupalGet('/admin/structure/templates/add');

    $page
      ->findField('label')
      ->setValue('Disabled Template');
    $page
      ->findField('id')
      ->setValue('disabled_template');
    $page
      ->findField('theme_suggestion')
      ->setValue('block');
    $page
      ->findField('template_code')
      ->setValue('{{ content }}' . PHP_EOL . 'Disabled Block template');
    $page
      ->findField('themes[grant]')
      ->check();
    $page
      ->findField('status')
      ->uncheck();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Disabled Template Twig template was created but is disabled.');

    // Create another Twig UI template to test display of allowed
    // theme options.
    $this->drupalGet('/admin/structure/templates/add');

    // Verify available theme checkboxes.
    $this->assertNotNull($page->findField('themes[stark]'));
    $this->assertNotNull($page->findField('themes[grant]'));

    $page
      ->findField('label')
      ->setValue('Another Template');
    $page
      ->findField('id')
      ->setValue('another_template');
    $page
      ->findField('theme_suggestion')
      ->setValue('example_another_template');
    $page
      ->findField('template_code')
      ->setValue('{{ content }}');
    $page
      ->findField('themes[stark]')
      ->check();
    $page
      ->findField('themes[grant]')
      ->check();
    $page->pressButton('Save');

    // Update config to remove 'stark' from available themes.
    $config = \Drupal::service('config.factory')->getEditable('twig_ui.settings');
    $config->setData([
      'allowed_themes' => 'selected',
      'allowed_theme_list' => [
        'grant',
      ],
      'default_selected_themes' => [
        'grant',
      ],
    ]);
    $config->save();

    // Verify grandfathered checkbox is still rendered with language.
    $this->drupalGet('/admin/structure/templates/another_template/edit');

    $this->assertNotNull($page->findField('themes[stark]'));
    $this->assertNotNull($page->findField('themes[grant]'));
    $assert_session->pageTextContains('If deselected, denoted themes will no longer be available as an option due to the Allowed theme list setting.');

    // Verify disallowed theme is no longer available as an option on a
    // new template.
    $this->drupalGet('/admin/structure/templates/add');

    $this->assertNull($page->findField('themes[stark]'));
    $this->assertNotNull($page->findField('themes[grant]'));

    // Test cloning of template.
    $this->drupalGet('/admin/structure/templates/' . $this->template->id() . '/clone');

    // Verify field alterations from cloned entity.
    $element = $page
      ->findField('label')
      ->getValue();
    $this->assertEquals($element, 'Clone of Node');
    $element = $page
      ->findField('id')
      ->getValue();
    $this->assertEquals($element, 'clone_node');
    $this->assertTrue($page->hasUncheckedField('themes[grant]'));

    // Change theme suggestion and select a theme so clone can be saved.
    $page
      ->findField('theme_suggestion')
      ->setValue('node__clone');
    $page
      ->findField('themes[grant]')
      ->check();
    $page
      ->findField('status')
      ->check();

    $page->pressButton('Save');

    $assert_session->pageTextContains('The Clone of Node Twig template was created.');

    // Verify data is stored correctly.
    $template = $twig_template_storage->loadByProperties(['id' => 'clone_node']);
    $template = array_shift($template);

    $this->assertSame($template->get('label'), 'Clone of Node');
    $this->assertSame($template->get('theme_suggestion'), 'node__clone');
    $this->assertSame($template->get('template_code'), '{{ content }}' . PHP_EOL . 'Test template 1');
    $this->assertSame($template->get('themes'), ['grant']);
    $this->assertSame($template->get('status'), TRUE);
  }

}
