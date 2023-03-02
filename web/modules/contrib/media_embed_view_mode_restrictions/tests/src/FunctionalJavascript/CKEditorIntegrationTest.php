<?php

namespace Drupal\Tests\media_embed_view_mode_restrictions\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests CKEditor and Media Library Integration.
 *
 * @group media_embed_view_mode_restrictions
 */
class CKEditorIntegrationTest extends MediaEmbedViewModeRestrictionsTestBase {

  use CKEditorTestTrait;
  use TestFileCreationTrait;

  /**
   * Media object.
   *
   * @var \Drupal\media\Entity\Media
   */
  protected $media;

  /**
   * Another media object.
   *
   * @var \Drupal\media\Entity\Media
   */
  protected $media2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ckeditor',
    'media_library',
    'media_embed_view_mode_restrictions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    Editor::create([
      'editor' => 'ckeditor',
      'format' => 'media_embed_test',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'Main',
                'items' => [
                  'Source',
                  'Undo',
                  'Redo',
                ],
              ],
            ],
            [
              [
                'name' => 'Embeds',
                'items' => [
                  'DrupalMediaLibrary',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'image_bundle_1',
      'mode' => 'viewmode1',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
      ->removeComponent('created')
      ->removeComponent('uid')
      ->setComponent('field_media_image', [
        'label' => 'visually_hidden',
        'type' => 'image',
        'settings' => [
          'image_style' => 'thumbnail',
          'image_link' => 'file',
        ],
        'third_party_settings' => [],
        'weight' => 1,
        'region' => 'content',
      ])
      ->save();
    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'image_bundle_1',
      'mode' => 'viewmode2',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
      ->removeComponent('created')
      ->removeComponent('uid')
      ->setComponent('field_media_image', [
        'label' => 'visually_hidden',
        'type' => 'image',
        'settings' => [
          'image_style' => 'medium',
          'image_link' => 'file',
        ],
        'third_party_settings' => [],
        'weight' => 1,
        'region' => 'content',
      ])
      ->save();
    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'image_bundle_2',
      'mode' => 'viewmode3',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
      ->removeComponent('created')
      ->removeComponent('uid')
      ->setComponent('field_media_image_1', [
        'label' => 'visually_hidden',
        'type' => 'image',
        'settings' => [
          'image_style' => 'large',
          'image_link' => 'file',
        ],
        'third_party_settings' => [],
        'weight' => 1,
        'region' => 'content',
      ])
      ->save();

    $file = File::create([
      'uri' => $this
        ->getTestFiles('image')[0]->uri,
    ]);
    $file->save();

    $this->media = Media::create([
      'bundle' => 'image_bundle_1',
      'name' => 'Bundle 1 Image',
      'field_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media->save();
    $this->media2 = Media::create([
      'bundle' => 'image_bundle_2',
      'name' => 'Bundle 2 Image',
      'field_media_image_1' => [
        [
          'target_id' => $file->id(),
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media2->save();

    $this->drupalCreateContentType(['type' => 'blog']);
  }

  /**
   * Tests CKEditor and Media Library integration.
   */
  public function testIntegration() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $session->resizeWindow(1200, 2000);

    // Configure test format.
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');
    $page->checkField('filters[media_embed][status]');
    $page->checkField('filters[media_embed][settings][allowed_media_types][image_bundle_1]');
    $page->checkField('filters[media_embed][settings][allowed_media_types][image_bundle_2]');

    // Configure Image Bundle 1.
    $element = $page->find('xpath', '//*[@id="edit-filters-media-embed-settings-bundle-view-modes-image-bundle-1"]/summary');
    $element->click();
    $page->fillField('filters[media_embed][settings][bundle_view_modes][image_bundle_1][default_view_mode]', 'viewmode2');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][image_bundle_1][allowed_view_modes][viewmode1]');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][image_bundle_1][allowed_view_modes][viewmode2]');

    // Configure Image Bundle 2.
    $element = $page->find('xpath', '//*[@id="edit-filters-media-embed-settings-bundle-view-modes-image-bundle-2"]/summary');
    $element->click();
    $page->fillField('filters[media_embed][settings][bundle_view_modes][image_bundle_2][default_view_mode]', 'viewmode3');
    $page->checkField('filters[media_embed][settings][bundle_view_modes][image_bundle_2][allowed_view_modes][viewmode3]');
    $page->pressButton('Save configuration');

    // Update test user role with additional permissions.
    $role_ids = $this->user->getRoles(TRUE);
    $role_id = array_shift($role_ids);
    /** @var  \Drupal\user\Entity\Role */
    $role = Role::load($role_id);
    $this->grantPermissions($role, [
      'use text format test_format',
      'access media overview',
      'create blog content',
      'edit any blog content',
    ]);

    // Create a new blog node to test insertion of media.
    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('drupalmedialibrary');
    $this->assertNotEmpty($assert_session->waitForId('drupal-modal'));

    // Verify Image Bundle 1 tab is active.
    $assert_session->elementExists('xpath', '//*[contains(@class, "js-media-library-menu")]//a[@data-title="Image Bundle 1"]')->has('css', '.active');

    // Select media item and insert.
    $assert_session->elementExists('css', '.js-media-library-item')->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');

    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media', 2000));
    $this->pressEditorButton('source');

    // Verify default view mode is applied.
    $value = $assert_session->elementExists('css', 'textarea.cke_source')->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $drupal_media = $xpath->query('//drupal-media')[0];
    $expected_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->media->uuid(),
      'data-view-mode' => 'viewmode2',
    ];
    foreach ($expected_attributes as $name => $expected) {
      $this->assertSame($expected, $drupal_media->getAttribute($name));
    }

    $page->fillField('Title', 'Test Page');
    $page->pressButton('Save');

    // Check the correct image style is rendered on front end.
    // The 'medium' image style is used exclusively by the 'viewmode2'
    // view mode.
    $element = $page->find('css', 'img');
    $src = $element->getAttribute('src');
    $this->assertTrue(strpos($src, 'styles/medium/public') !== FALSE);

    // Go back and edit the embedded media.
    $this->drupalGet('/node/1/edit');
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');

    $page->pressButton('Edit media');
    $this->waitForMediaDialog();

    // Verify view mode options are properly restricted on edit form.
    $assert_session->elementExists('xpath', '//*[@name="attributes[data-view-mode]"]');
    $assert_session->optionExists('attributes[data-view-mode]', 'viewmode1');
    $assert_session->optionExists('attributes[data-view-mode]', 'viewmode2');
    $assert_session->optionNotExists('attributes[data-view-mode]', 'viewmode3');

    // Create another blog node.
    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('drupalmedialibrary');
    $this->assertNotEmpty($assert_session->waitForId('drupal-modal'));

    // Select Image Bundle 2 tab.
    $assert_session->elementExists('xpath', '//*[contains(@class, "js-media-library-menu")]//a[@data-title="Image Bundle 2"]')->click();
    $assert_session->waitForElementVisible('xpath', '//form[@data-drupal-media-type="image_bundle_2"]');

    // Select media item and insert.
    $element = $page->find('css', '.js-media-library-item');
    $element->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');

    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media', 2000));
    $this->pressEditorButton('source');

    // Verify default view mode is applied.
    $value = $assert_session->elementExists('css', 'textarea.cke_source')->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $drupal_media = $xpath->query('//drupal-media')[0];
    $expected_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->media2->uuid(),
      'data-view-mode' => 'viewmode3',
    ];
    foreach ($expected_attributes as $name => $expected) {
      $this->assertSame($expected, $drupal_media->getAttribute($name));
    }

    $page->fillField('Title', 'Test Page 2');
    $page->pressButton('Save');

    // Check the correct image style is rendered on front end.
    // The 'large' image style is used exclusively by the 'viewmode3'
    // view mode.
    $element = $page->find('css', 'img');
    $src = $element->getAttribute('src');
    $this->assertTrue(strpos($src, 'styles/large/public') !== FALSE);

    // Go back and edit the embedded media.
    $this->drupalGet('/node/2/edit');
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');

    $page->pressButton('Edit media');
    $this->waitForMediaDialog();

    // Verify view mode options are properly restricted on edit form.
    // Whereas only one option is available, none should be presented.
    $assert_session->elementNotExists('xpath', '//*[@name="attributes[data-view-mode]"]');
  }

  /**
   * Waits for the form that allows editing metadata.
   *
   * "Borrowed" from
   * Drupal\Tests\media\FunctionalJavascript\CKEditorIntegrationTest.
   * Renamed from waitForMetadataDialog().
   */
  protected function waitForMediaDialog() {
    $page = $this->getSession()->getPage();
    $this->getSession()->switchToIFrame();
    // Wait for the dialog to open.
    $result = $page->waitFor(10, function ($page) {
      $metadata_editor = $page->find('css', 'form.editor-media-dialog');
      return !empty($metadata_editor);
    });
    $this->assertTrue($result);
  }

}
