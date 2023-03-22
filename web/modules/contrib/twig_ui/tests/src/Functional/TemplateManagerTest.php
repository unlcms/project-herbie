<?php

namespace Drupal\Tests\twig_ui\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Tests\BrowserTestBase;
use Drupal\twig_ui\Entity\TwigTemplate;
use Drupal\Tests\twig_ui\Traits\HtaccessTestTrait;

/**
 * Test the TemplateManager service.
 *
 * @group twig_ui
 *
 * @coversDefaultClass \Drupal\twig_ui\TemplateManager
 */
class TemplateManagerTest extends BrowserTestBase {

  use HtaccessTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'twig_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The Twig UI Template Manager service.
   *
   * @var \Drupal\twig_ui\TemplateManager
   */
  protected $templateManager;

  /**
   * {@inheritdoc}
   */
  public function setup() : void {
    parent::setup();
    \Drupal::service('theme_installer')->install(['grant']);
    \Drupal::service('theme_installer')->install(['perkins']);

    $this->templateManager = \Drupal::service('twig_ui.template_manager');
  }

  /**
   * Create test templates.
   */
  protected function createTemplates() {
    $this->template = TwigTemplate::create([
      'id' => 'node',
      'label' => 'Node',
      'theme_suggestion' => 'node',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 1',
      'themes' => [
        'stark',
        'grant',
      ],
    ]);
    $this->template->save();

    $this->template2 = TwigTemplate::create([
      'id' => 'node_page',
      'label' => 'Node - Page',
      'theme_suggestion' => 'node__page',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 2',
      'themes' => [
        'grant',
      ],
    ]);
    $this->template2->save();

    $this->template3 = TwigTemplate::create([
      'status' => FALSE,
      'id' => 'node_event',
      'label' => 'Node - Event',
      'theme_suggestion' => 'node__event',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 3 (originally disabled)',
      'themes' => [
        'grant',
      ],
    ]);
    $this->template3->save();
  }

  /**
   * Tests ::getTemplates().
   *
   * @covers ::getTemplates
   */
  public function testGetTemplates() {
    $this->createTemplates();
    $templates = $this->templateManager->getTemplates();
    $this->assertTrue(array_key_exists('node', $templates));
    $this->assertTrue(array_key_exists('node_page', $templates));
    $this->assertFalse(array_key_exists('node_chris', $templates));
  }

  /**
   * Tests ::getTemplatesByTheme().
   *
   * @covers ::getTemplatesByTheme
   */
  public function testGetTemplatesByTheme() {
    $this->createTemplates();
    $templates = $this->templateManager->getTemplatesByTheme('stark');
    $this->assertTrue(array_key_exists('node', $templates));
    $this->assertFalse(array_key_exists('node_page', $templates));
    $templates = $this->templateManager->getTemplatesByTheme('grant');
    $this->assertTrue(array_key_exists('node', $templates));
    $this->assertTrue(array_key_exists('node_page', $templates));
    $templates = $this->templateManager->getTemplatesByTheme('perkins');
    $this->assertNull($templates);
  }

  /**
   * Tests ::getTemplate().
   *
   * @covers ::getTemplate
   */
  public function testGetTemplate() {
    $this->createTemplates();
    $template = $this->templateManager->getTemplate('node');
    $this->assertEquals($template->label(), 'Node');
    $template = $this->templateManager->getTemplate('node_page');
    $this->assertEquals($template->label(), 'Node - Page');
    $template = $this->templateManager->getTemplate('invalid');
    $this->assertNull($template);
  }

  /**
   * Tests ::templateExists().
   *
   * @covers ::templateExists
   */
  public function testTemplateExists() {
    $this->createTemplates();
    $this->assertEquals($this->templateManager->templateExists('node', 'stark'), 'node');
    $this->assertEquals($this->templateManager->templateExists('node', 'grant'), 'node');
    $this->assertFalse($this->templateManager->templateExists('node', 'perkins'));
    $this->assertEquals($this->templateManager->templateExists('node__page', 'grant'), 'node_page');
    $this->assertFalse($this->templateManager->templateExists('node__page', 'stark'));
    $this->assertFalse($this->templateManager->templateExists('node__page', 'perkins'));
    $this->assertFalse($this->templateManager->templateExists('node__event', 'grant'));
    $this->assertFalse($this->templateManager->templateExists('node__event', 'stark'));
    $this->assertFalse($this->templateManager->templateExists('node__event', 'perkins'));
  }

  /**
   * Tests ::getActiveThemes().
   *
   * @covers ::getActiveThemes
   */
  public function testGetActiveThemes() {
    $active_themes = $this->templateManager->getActiveThemes();

    $this->assertTrue(array_key_exists('stark', $active_themes));
    $this->assertTrue(array_key_exists('grant', $active_themes));
    $this->assertTrue(array_key_exists('perkins', $active_themes));
    $this->assertFalse(array_key_exists('bartik', $active_themes));
  }

  /**
   * Tests ::getAllowedThemes().
   *
   * @covers ::getAllowedThemes
   */
  public function testGetAllowedThemes() {
    $allowed_themes = $this->templateManager->getAllowedThemes();

    $this->assertTrue(in_array('stark', $allowed_themes));
    $this->assertTrue(in_array('grant', $allowed_themes));
    $this->assertTrue(in_array('perkins', $allowed_themes));
    $this->assertFalse(in_array('bartik', $allowed_themes));

    // Update config to change allowed themes to 'selected' and remove 'stark'
    // from available themes.
    $config = \Drupal::service('config.factory')->getEditable('twig_ui.settings');
    $config->setData([
      'allowed_themes' => 'selected',
      'allowed_theme_list' => [
        'grant',
        'perkins',
      ],
      'default_selected_themes' => [],
    ]);
    $config->save();

    $allowed_themes = $this->templateManager->getAllowedThemes();

    $this->assertFalse(in_array('stark', $allowed_themes));
    $this->assertTrue(in_array('grant', $allowed_themes));
    $this->assertTrue(in_array('perkins', $allowed_themes));
    $this->assertFalse(in_array('bartik', $allowed_themes));
  }

  /**
   * Tests ::syncTemplateFiles().
   *
   * @covers ::syncTemplateFiles
   */
  public function testSyncTemplateFiles() {
    $this->createTemplates();

    // Create Twig UI template.
    $template = TwigTemplate::create([
      'id' => 'node_news',
      'label' => 'Node - News',
      'theme_suggestion' => 'node__news',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 1',
      'themes' => [
        'grant',
        'perkins',
      ],
    ]);

    // Don't invoke save() method on TwigTemplate object.
    // Instead test syncTemplateFiles() directly.
    // Test initial file creation.
    $this->templateManager->syncTemplateFiles($template);

    $this->assertFileExists('public://twig_ui/grant/node--news.html.twig');
    $this->assertStringEqualsFile('public://twig_ui/grant/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 1');
    $this->assertFileExists('public://twig_ui/perkins/node--news.html.twig');
    $this->assertStringEqualsFile('public://twig_ui/perkins/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 1');
    $this->assertFileNotExists('public://twig_ui/stark/node--news.html.twig');

    // Test contents of files written to file system.
    $template2 = TwigTemplate::create([
      'id' => 'node_news',
      'label' => 'Node - News',
      'theme_suggestion' => 'node__news',
      'template_code' => '{{ content }}' . PHP_EOL . 'Test template 2',
      'themes' => [
        'stark',
      ],
    ]);
    $this->templateManager->syncTemplateFiles($template2);

    // Verify no cross contamination among themes for templates with the
    // same name.
    $this->assertFileExists('public://twig_ui/grant/node--news.html.twig');
    $this->assertStringEqualsFile('public://twig_ui/grant/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 1');
    $this->assertFileExists('public://twig_ui/perkins/node--news.html.twig');
    $this->assertStringEqualsFile('public://twig_ui/perkins/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 1');
    $this->assertFileExists('public://twig_ui/stark/node--news.html.twig');
    $this->assertStringEqualsFile('public://twig_ui/stark/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 2');

    // Test change of theme suggestion.
    // ::syncTemplateFiles() is expecting $template->original, and the
    // 'original' property is normally added to the template object for post-
    // action hooks. We fake it here.
    $template->set('original', clone $template);
    $template->set('theme_suggestion', 'node__news_page');
    $this->templateManager->syncTemplateFiles($template);

    $this->assertFileExists('public://twig_ui/stark/node--news.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node--news.html.twig');
    $this->assertFileNotExists('public://twig_ui/perkins/node--news.html.twig');
    $this->assertFileNotExists('public://twig_ui/stark/node--news-page.html.twig');
    $this->assertFileExists('public://twig_ui/grant/node--news-page.html.twig');
    $this->assertFileExists('public://twig_ui/perkins/node--news-page.html.twig');
    $this->assertFileNotExists('public://twig_ui/stark/node--event.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node--event.html.twig');
    $this->assertFileNotExists('public://twig_ui/perkins/node--event.html.twig');

    // Test change in selected themes.
    $template->set('original', clone $template);
    $template->set('themes', ['grant']);
    $this->templateManager->syncTemplateFiles($template);

    $this->assertFileExists('public://twig_ui/stark/node--news.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node--news.html.twig');
    $this->assertFileNotExists('public://twig_ui/perkins/node--news.html.twig');
    $this->assertFileNotExists('public://twig_ui/stark/node--news-page.html.twig');
    $this->assertFileExists('public://twig_ui/grant/node--news-page.html.twig');
    $this->assertFileNotExists('public://twig_ui/perkins/node--news-page.html.twig');
    $this->assertFileNotExists('public://twig_ui/stark/node--event.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node--event.html.twig');
    $this->assertFileNotExists('public://twig_ui/perkins/node--event.html.twig');

    // Test change in template code.
    $template->set('original', clone $template);
    $template->set('template_code', '{{ content }}' . PHP_EOL . 'Test template 3',);

    $this->assertStringEqualsFile('public://twig_ui/stark/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 2');
    $this->assertStringEqualsFile('public://twig_ui/grant/node--news-page.html.twig', '{{ content }}' . PHP_EOL . 'Test template 1');

    $this->templateManager->syncTemplateFiles($template);

    $this->assertStringEqualsFile('public://twig_ui/stark/node--news.html.twig', '{{ content }}' . PHP_EOL . 'Test template 2');
    $this->assertStringEqualsFile('public://twig_ui/grant/node--news-page.html.twig', '{{ content }}' . PHP_EOL . 'Test template 3');

    // Test change in status.
    $this->template3->enable();
    $this->template3->save();

    $this->assertFileNotExists('public://twig_ui/stark/node--event.html.twig');
    $this->assertFileExists('public://twig_ui/grant/node--event.html.twig');
    $this->assertFileNotExists('public://twig_ui/perkins/node--event.html.twig');

    $this->assertStringEqualsFile('public://twig_ui/grant/node--event.html.twig', '{{ content }}' . PHP_EOL . 'Test template 3 (originally disabled)');
  }

  /**
   * Tests ::deleteTemplateFiles().
   *
   * @covers ::deleteTemplateFiles
   */
  public function testDeleteTemplateFiles() {
    $this->createTemplates();

    $this->assertFileExists('public://twig_ui/stark/node.html.twig');
    $this->assertFileExists('public://twig_ui/grant/node.html.twig');
    $this->assertFileExists('public://twig_ui/grant/node--page.html.twig');

    $this->templateManager->deleteTemplateFiles($this->template);

    $this->assertFileNotExists('public://twig_ui/stark/node.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node.html.twig');
    $this->assertFileExists('public://twig_ui/grant/node--page.html.twig');

    $this->templateManager->deleteTemplateFiles($this->template2);

    $this->assertFileNotExists('public://twig_ui/stark/node.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node.html.twig');
    $this->assertFileNotExists('public://twig_ui/grant/node--page.html.twig');
  }

  /**
   * Tests ::getDirectoryPathByTheme().
   *
   * @covers ::getDirectoryPathByTheme
   */
  public function testGetDirectoryPathByTheme() {
    $this->createTemplates();
    $this->assertEquals($this->templateManager->getDirectoryPathByTheme('grant'), 'public://twig_ui/grant');
    $this->assertEquals($this->templateManager->getDirectoryPathByTheme('grant', FALSE), PublicStream::basePath() . '/twig_ui/grant');
    $this->assertEquals($this->templateManager->getDirectoryPathByTheme('perkins'), 'public://twig_ui/perkins');
    $this->assertEquals($this->templateManager->getDirectoryPathByTheme('perkins', FALSE), PublicStream::basePath() . '/twig_ui/perkins');
  }

  /**
   * Tests ::getTemplatePath().
   *
   * @covers ::getTemplatePath
   */
  public function testGetTemplatePath() {
    $this->createTemplates();
    $this->assertEquals($this->templateManager->getTemplatePath($this->template2, 'grant'), 'public://twig_ui/grant/node--page.html.twig');
  }

  /**
   * Tests ::getTemplateFileName().
   *
   * @covers ::getTemplateFileName
   */
  public function testGetTemplateFileName() {
    $this->createTemplates();
    $this->assertEquals($this->templateManager->getTemplateFileName($this->template2), 'node--page.html.twig');
  }

  /**
   * Tests ::prepareTemplatesDirectory().
   *
   * @covers ::prepareTemplatesDirectory
   */
  public function testPrepareTemplatesDirectory() {
    // The templates directory is prepared during installation.
    // Remove it before testing.
    $this->deleteTemplatesDirectory();

    // Execute ::prepareTemplatesDirectory().
    $return = $this->templateManager->prepareTemplatesDirectory();
    $this->assertEquals($return, TRUE);
    $this->assertFileExists('public://twig_ui/.htaccess');

    // Attempt to create templates directory when public:// is unwritable.
    $this->deleteTemplatesDirectory();
    $this->makeUnwritable('public://');
    $this->assertDirectoryNotIsWritable('public://');

    $return = $this->templateManager->prepareTemplatesDirectory();
    $this->assertEquals($return, 'Unable to create templates directory');
  }

}
