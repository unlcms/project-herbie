<?php

namespace Drupal\Tests\twig_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the template form.
 *
 * @group twig_ui
 */
class TemplateFormTest extends WebDriverTestBase {

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test template editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $templateEditorUser;

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
        'administer twig ui templates settings',
        'load twig templates from file system',
      ]);

    // Create an power user.
    $this->templateEditorUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer blocks',
        'administer twig templates',
        'administer twig ui templates settings',
      ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Test "template load" portion of the TwigTemplateForm.
   *
   * @covers \Drupal\twig_ui\Entity\TwigTemplateForm
   */
  public function testTemplateFormTemplateLoad() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Verify users without the 'load twig templates from file system' do not
    // see the 'template load' details element.
    $this->drupalLogin($this->templateEditorUser);
    $this->drupalGet('/admin/structure/templates/add');

    $element = $page->find('xpath', '//details[@id="edit-template-load"]');
    $this->assertNull($element);

    // Verify users with the 'load twig templates from file system' do see the
    // 'template load' details element.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/templates/add');

    $element = $page->find('xpath', '//details[@id="edit-template-load"]');
    $this->assertNotNull($element);

    // Expand details element.
    $element = $page->find('xpath', '//*[@id="edit-template-load"]/summary');
    $element->click();

    // Set default value for the template_code field to replace later.
    $page
      ->findField('template_code')
      ->setValue('{{ content }}' . PHP_EOL . 'Template test code');

    // Verify initial 'template load' form values.
    $element = $page->findField('theme')->getValue();
    $this->assertEquals($element, '_none');

    $element = $page->findField('template')->getValue();
    $this->assertEquals($element, '_none');

    $element = $page->find('xpath', '//details[@id="edit-template-load"]//div[contains(@class, "file-path")]/span[contains(@class, "value")]');
    $this->assertEmpty($element->getHtml());

    $element = $page->find('xpath', '//details[@id="edit-template-load"]//div[contains(@class, "template-code")]/pre');
    $this->assertEquals($element->getHtml(), 'Select a template.');

    // Change 'theme' field value.
    $page
      ->findField('theme')
      ->setValue('grant');
    $assert_session->assertWaitOnAjaxRequest();

    // Change the 'template' field value, which should have updated its options
    // upon the change of the 'theme' field.
    $element = $page->findField('template');
    // It shouldn't be necessary to ->click() before ->setValue().
    $element->click();
    $element
      ->setValue('block');
    $assert_session->assertWaitOnAjaxRequest();

    // Test loading of the block template file for the Grant theme.
    $extension_path_resolver = \Drupal::service('extension.path.resolver');
    $block_module_path = $extension_path_resolver->getPath('module', 'block');
    $template_path = $block_module_path . '/templates/block.html.twig';

    // Verify the file path.
    $element = $page->find('xpath', '//details[@id="edit-template-load"]//div[contains(@class, "file-path")]/span[contains(@class, "value")]');
    $this->assertEquals($element->getHtml(), $template_path);

    // Verify the previewed template Twig markup.
    $abs_template_path = \Drupal::service('file_system')->realpath($template_path);
    $template_code = file_get_contents($abs_template_path);
    $element = $page->find('xpath', '//details[@id="edit-template-load"]//div[contains(@class, "template-code")]/pre');
    $this->assertEquals($element->getHtml(), htmlentities($template_code, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML401));

    // Insert the template code into the template_code field above.
    $page->pressButton('Insert');
    $assert_session->assertWaitOnAjaxRequest();

    // Verify template_code field has updated.
    $element = $page
      ->findField('template_code')
      ->getValue();

    $this->assertEquals($template_code, $element);

    // Test insertion with CodeMirror.
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['codemirror_editor']);

    $this->drupalGet('/admin/structure/templates/add');

    // Expand details element.
    $element = $page->find('xpath', '//*[@id="edit-template-load"]/summary');
    $element->click();

    // Change 'theme' field value.
    $page
      ->findField('theme')
      ->setValue('grant');
    $assert_session->assertWaitOnAjaxRequest();

    // Change the 'template' field value, which should have updated its options
    // upon the change of the 'theme' field.
    $element = $page->findField('template');
    // It shouldn't be necessary to ->click() before ->setValue().
    $element->click();
    $element
      ->setValue('block');
    $assert_session->assertWaitOnAjaxRequest();

    // Insert the template code into the template_code field above.
    $page->pressButton('Insert');
    $assert_session->assertWaitOnAjaxRequest();

    $this->assertEditorValue($template_code);
  }

  /**
   * Test submission of TwigTemplateForm.
   *
   * @covers \Drupal\twig_ui\Entity\TwigTemplateForm
   */
  public function testFormSubmission() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/templates/add');

    $page
      ->findField('label')
      ->setValue('Test Template');
    $this->exposeMachineName();
    $page
      ->findField('id')
      ->setValue('test_template');
    $page
      ->findField('theme_suggestion')
      ->setValue('test--suggestion');

    $page
      ->findField('themes[grant]')
      ->check();

    // Expand details element.
    $element = $page->find('xpath', '//*[@id="edit-template-load"]/summary');
    $element->click();

    // Change 'theme' field value.
    $page
      ->findField('theme')
      ->setValue('grant');
    $assert_session->assertWaitOnAjaxRequest();

    // Change the 'template' field value, which should have updated its options
    // upon the change of the 'theme' field.
    $element = $page->findField('template');
    // It shouldn't be necessary to ->click() before ->setValue().
    $element->click();
    $element
      ->setValue('block');
    $assert_session->assertWaitOnAjaxRequest();

    // Insert the template code into the template_code field above.
    $page->pressButton('Insert');
    $assert_session->assertWaitOnAjaxRequest();

    // Because the 'template' field will have a submitted value not defined as
    // an option in $form in TwigTemplateForm::form(), validation will fail.
    // This test tests that this error is unset by
    // TwigTemplateform::validateForm(), which will allow the form to be
    // submitted with the 'template' field value being disregarded.
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Test Template Twig template was created.');
  }

  /**
   * Tests CodeMirror integration.
   */
  public function testCodeMirrorIntegration() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['codemirror_editor']);

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/structure/templates/add');

    // Verify default configuration in data-codemirror attribute.
    $element = $page->find('xpath', '//textarea[@name="template_code"]');
    $data_codemirror = $element->getAttribute('data-codemirror');
    $this->assertEquals($data_codemirror, '{"mode":"text\/x-twig","lineNumbers":true}');

    // Verify selected default behavior.
    $assert_session->elementExists('css', '.form-item-template-code .cme-toolbar');

    $toolbar_xpath = '//div[contains(@class, "form-item-template-code ")]//div[@class = "cme-toolbar"]';
    $assert_session->elementExists('xpath', $toolbar_xpath . '/*[@data-cme-button = "bold"]');
    $assert_session->elementExists('xpath', $toolbar_xpath . '/*[@data-cme-button = "italic"]');
    $assert_session->elementExists('xpath', $toolbar_xpath . '/*[@data-cme-button = "underline"]');

    // Update CodeMirror config.
    $config = $this->container->get('config.factory')->getEditable('twig_ui.settings');
    $config->set('codemirror_config', "lineNumbers: false\nbuttons:\n  - bold\n  - italic\n");
    $config->save();

    $this->drupalGet('/admin/structure/templates/add');

    // Verify updated configuration in data-codemirror attribute.
    $element = $page->find('xpath', '//textarea[@name="template_code"]');
    $data_codemirror = $element->getAttribute('data-codemirror');
    $this->assertEquals($data_codemirror, '{"mode":"text\/x-twig","lineNumbers":false,"buttons":["bold","italic"]}');

    // Verify selected updated behavior.
    $toolbar_xpath = '//div[contains(@class, "form-item-template-code ")]//div[@class = "cme-toolbar"]';
    $assert_session->elementExists('xpath', $toolbar_xpath . '/*[@data-cme-button = "bold"]');
    $assert_session->elementExists('xpath', $toolbar_xpath . '/*[@data-cme-button = "italic"]');
    $assert_session->elementNotExists('xpath', $toolbar_xpath . '/*[@data-cme-button = "underline"]');
  }

  /**
   * Asserts editor value.
   *
   * "Borrowed" from
   * Drupal\Tests\codemirror_editor\FunctionalJavascript\TestBase.
   */
  protected function assertEditorValue($expected_value) {
    $script = 'document.querySelector(".CodeMirror").CodeMirror.getValue();';
    $value = $this->getSession()
      ->evaluateScript($script);
    self::assertSame($expected_value, $value);
  }

  /**
   * Makes the machine-name field visible.
   */
  protected function exposeMachineName() {
    $script = 'document.getElementsByClassName("js-form-item-id")[0].classList.remove("visually-hidden");';
    $this->getSession()
      ->evaluateScript($script);
  }

}
