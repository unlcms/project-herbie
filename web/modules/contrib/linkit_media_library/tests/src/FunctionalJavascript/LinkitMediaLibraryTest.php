<?php

namespace Drupal\Tests\linkit_media_library\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests Linkit Media Library integration.
 *
 * @group linkit_media_library
 */
class LinkitMediaLibraryTest extends WebDriverTestBase {

  use CKEditorTestTrait;
  use MediaTypeCreationTrait;
  use ProfileCreationTrait;
  use TestFileCreationTrait;

  /**
   * Filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filter;

  /**
   * Text editor config entity.
   *
   * @var \Drupal\editor\EditorInterface
   */
  protected $editor;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'linkit_media_library',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add a text format.
    $this->filter = FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [
        'linkit' => [
          'status' => TRUE,
          'settings' => [
            'title' => FALSE,
          ],
        ],
      ],
    ]);
    $this->filter->save();

    // Set up editor.
    $this->editor = Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor',
    ]);
    $this->editor->setSettings([
      'toolbar' => [
        'rows' => [
          [
            [
              'name' => 'Linking',
              'items' => [
                'DrupalLink',
              ],
            ],
          ],
        ],
      ],
      'plugins' => [
        'drupallink' => [
          'linkit_enabled' => TRUE,
          'linkit_profile' => 'default',
        ],
      ],
    ]);
    $this->editor->save();

    // Create a 'document' media bundle.
    $this->createMediaType('file', ['id' => 'document']);

    // Create a test file and media entity.
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    Media::create([
      'bundle' => 'document',
      'name' => 'Test document',
      'field_media_document' => [
        [
          'target_id' => 1,
        ],
      ],
    ])->save();

    // Create test content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Create and login test user.
    $this->testUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests that media links are correctly inserted into the editor.
   */
  public function testLinkitMediaLibraryInsertion() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('drupallink');
    $assert_session->waitForId('drupal-modal');
    // Verify 'Media Library' button is rendered in link modal.
    $assert_session->elementExists('xpath', '//div[@id="drupal-modal"]//input[@name="linkit_media_library_opener"]');
    $page->pressButton('Media Library');
    $assert_session->waitForElement('css', '.media-library-widget-modal');
    $page->checkField('Select Test document');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $assert_session->waitForElementRemoved('css', '.media-library-widget-modal');
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    // Verify link is correctly inserted.
    $link = $assert_session->elementExists('css', 'a[href="/media/1"]');
    $this->assertNotEmpty($link);
  }

  /**
   * Tests Media Library button rendering.
   */
  public function testButtonRendering() {
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('drupallink');
    $assert_session->waitForId('drupal-modal');
    // Verify 'Media Library' button is rendered in link modal.
    $assert_session->elementExists('xpath', '//div[@id="drupal-modal"]//input[@name="linkit_media_library_opener"]');

    // Update editor settings to disable the Linkit CKEditor plugin.
    $this->editor->setSettings([
      'toolbar' => [
        'rows' => [
          [
            [
              'name' => 'Linking',
              'items' => [
                'DrupalLink',
              ],
            ],
          ],
        ],
      ],
      'plugins' => [
        'drupallink' => [
          'linkit_enabled' => FALSE,
          'linkit_profile' => '',
        ],
      ],
    ]);
    $this->editor->save();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('drupallink');
    $assert_session->waitForId('drupal-modal');
    // Verify 'Media Library' button is not rendered in link modal.
    $assert_session->elementNotExists('xpath', '//div[@id="drupal-modal"]//input[@name="linkit_media_library_opener"]');

    // Update editor settings to enable the Linkit CKEditor plugin.
    $this->editor->setSettings([
      'toolbar' => [
        'rows' => [
          [
            [
              'name' => 'Linking',
              'items' => [
                'DrupalLink',
              ],
            ],
          ],
        ],
      ],
      'plugins' => [
        'drupallink' => [
          'linkit_enabled' => TRUE,
          'linkit_profile' => 'default',
        ],
      ],
    ]);
    $this->editor->save();
    // Disable the Linkit filter.
    $this->filter->removeFilter('linkit');
    $this->filter->save();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('drupallink');
    $assert_session->waitForId('drupal-modal');
    // Verify 'Media Library' button is not rendered in link modal.
    $assert_session->elementNotExists('xpath', '//div[@id="drupal-modal"]//input[@name="linkit_media_library_opener"]');
  }

}
