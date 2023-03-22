<?php

declare(strict_types=1);

namespace Drupal\Tests\media_file_delete\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * This class provides methods specifically for testing something.
 *
 * @group media_file_delete
 */
class MediaFileDeleteSettingsTest extends BrowserTestBase {
  use TestFileCreationTrait;
  use MediaTypeCreationTrait;


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
    'image',
    'node',
    'file',
    'media',
    'media_file_delete',
    'media_test_views',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The image media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $imageMediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->imageMediaType = $this->createMediaType('image', [
      'id' => 'image',
      'new_revision' => TRUE,
    ]);

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a media entity with an image file.
   */
  protected function createMediaEntity(): array {
    $image = $this->getTestFiles('image')[0];
    assert($image instanceof \stdClass);
    assert(property_exists($image, 'uri'));
    $file = File::create([
      'uri' => $image->uri,
      'status' => 1,
      'uid' => $this->adminUser->id(),
    ]);
    $file->save();
    $media = Media::create([
      'bundle' => $this->imageMediaType->id(),
      'name' => $this->randomMachineName(),
      'field_media_image' => $file,
    ]);
    $media->save();
    return [
      'file' => $file,
      'media' => $media,
    ];
  }

  /**
   * Tests to see if the settings page exists and can be configured.
   */
  public function testSettings(): void {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/config/media/media_file_delete/settings');
    // Access page as an admin user:
    $session->statusCodeEquals(200);
    $session->elementTextEquals('css', 'h1', 'Media File Delete settings');
    $session->fieldExists('Delete files from file system when deleting media entities');
    $session->fieldExists('Disable user control of file deletion');
    // Enable settings checkboxes and see if they are saved.
    $page->checkField('Delete files from file system when deleting media entities');
    $page->checkField('Disable user control of file deletion');
    $page->pressButton('Save configuration');
    $session->pageTextContains('The configuration options have been saved.');
    $session->checkboxChecked('Delete files from file system when deleting media entities');
    $session->checkboxChecked('Disable user control of file deletion');
    // Access page as a non logged-in user:
    $this->drupalLogout();
    $this->drupalGet('/admin/config/media/media_file_delete/settings');
    $session->statusCodeEquals(403);
  }

  /**
   * Tests the delete file default setting on a single media entity.
   */
  public function testDeleteFileDefaultOnSingleMediaDeletion(): void {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Create file and media entity:
    $fileMediaArray = $this->createMediaEntity();
    // Check if file exists:
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    // Go to media deletion form and check default setting is FALSE:
    $this->drupalGet($fileMediaArray['media']->toUrl('delete-form'));
    $session->statusCodeEquals(200);
    $session->fieldExists('Also delete the associated file?');
    $session->checkboxNotChecked('Also delete the associated file?');
    // Delete media entity and check if file still exists:
    $page->pressButton('Delete');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The media item ' . $fileMediaArray['media']->getName() . ' has been deleted.');
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    // Delete file manually to clean up.
    $fileMediaArray['file']->delete();

    // Create new file and media entity:
    $fileMediaArray = $this->createMediaEntity();
    // Set default config to true:
    $this->config('media_file_delete.settings')->set('delete_file_default', 'TRUE')->save();

    // Check if file exists:
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    // Go to media deletion form and check default setting is TRUE:
    $this->drupalGet($fileMediaArray['media']->toUrl('delete-form'));
    $session->statusCodeEquals(200);
    $session->fieldExists('Also delete the associated file?');
    $session->checkboxChecked('Also delete the associated file?');
    // Delete media entity and check if file is deleted as well:
    $page->pressButton('Delete');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The media item ' . $fileMediaArray['media']->getName() . ' has been deleted.');
    $session->pageTextContains('Deleted the associated file ' . $fileMediaArray['file']->getFilename() . '.');
    $this->assertFalse(file_exists($fileMediaArray['file']->getFileUri()));
  }

  /**
   * Tests the delete file default setting on multiple media entities.
   */
  public function testDeleteFileDefaultOnMultipleMediaDeletion(): void {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Create files and media entities:
    $fileMediaArray = $this->createMediaEntity();
    $fileMediaArray2 = $this->createMediaEntity();
    // Check if files exists:
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    $this->assertTrue(file_exists($fileMediaArray2['file']->getFileUri()));
    // Go to media bulk form, delete multiple media entities and check if
    // default setting is FALSE:
    $this->drupalGet('test-media-bulk-form');
    $session->statusCodeEquals(200);
    $page->checkField('edit-media-bulk-form-0');
    $page->checkField('edit-media-bulk-form-1');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $session->fieldExists('Also delete the associated files?');
    $session->checkboxNotChecked('Also delete the associated files?');
    // Delete media entities and check if files still exists:
    $page->pressButton('Delete');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Deleted 2 items.');
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    $this->assertTrue(file_exists($fileMediaArray2['file']->getFileUri()));
    // Delete files manually to clean up:
    $fileMediaArray['file']->delete();
    $fileMediaArray2['file']->delete();

    // Create new files and media entities:
    $fileMediaArray = $this->createMediaEntity();
    $fileMediaArray2 = $this->createMediaEntity();
    // Set default config to true:
    $this->config('media_file_delete.settings')->set('delete_file_default', 'TRUE')->save();
    // Check if files exists:
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    $this->assertTrue(file_exists($fileMediaArray2['file']->getFileUri()));
    // Go to media deletion bulk form again and check default setting is TRUE:
    $this->drupalGet('test-media-bulk-form');
    $session->statusCodeEquals(200);
    $page->checkField('edit-media-bulk-form-0');
    $page->checkField('edit-media-bulk-form-1');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $session->fieldExists('Also delete the associated files?');
    $session->checkboxChecked('Also delete the associated files?');
    // Delete media entities and check if the files are deleted as well:
    $page->pressButton('Delete');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Deleted 2 items.');
    $session->pageTextContains('Deleted 2 associated files.');
    $this->assertFalse(file_exists($fileMediaArray['file']->getFileUri()));
    $this->assertFalse(file_exists($fileMediaArray2['file']->getFileUri()));
  }

  /**
   * Tests the disable user control setting on a single media entity.
   */
  public function testDisableDeleteControlonSingleMediaEntityDeletion(): void {
    $session = $this->assertSession();
    // Create file and media entity:
    $fileMediaArray = $this->createMediaEntity();
    // Check if file exists:
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    // Go to media deletion form and check if the deletion checkbox is present:
    $this->drupalGet($fileMediaArray['media']->toUrl('delete-form'));
    $session->statusCodeEquals(200);
    $session->fieldExists('Also delete the associated file?');
    $session->pageTextContains('Also delete the associated file?');

    // Set disable_delete_control to true:
    $this->config('media_file_delete.settings')->set('disable_delete_control', 'TRUE')->save();

    // Go to media deletion form again and check if the deletion checkbox is not
    // present anymore:
    $this->drupalGet($fileMediaArray['media']->toUrl('delete-form'));
    $session->statusCodeEquals(200);
    $session->fieldNotExists('Also delete the associated file?');
  }

  /**
   * Tests the disable user control setting on a multiple media entities.
   */
  public function testDisableDeleteControlonMultipleMediaEntityDeletion(): void {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Create file and media entity:
    $fileMediaArray = $this->createMediaEntity();
    $fileMediaArray2 = $this->createMediaEntity();
    // Check if file exists:
    $this->assertTrue(file_exists($fileMediaArray['file']->getFileUri()));
    $this->assertTrue(file_exists($fileMediaArray2['file']->getFileUri()));
    // Go to media deletion bulk form and check if the deletion checkbox is
    // present:
    $this->drupalGet('test-media-bulk-form');
    $session->statusCodeEquals(200);
    $page->checkField('edit-media-bulk-form-0');
    $page->checkField('edit-media-bulk-form-1');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $session->statusCodeEquals(200);
    $session->fieldExists('Also delete the associated files?');

    // Set disable_delete_control to true:
    $this->config('media_file_delete.settings')->set('disable_delete_control', 'TRUE')->save();

    // Go to media bulk deletion form again and check if the deletion checkbox
    // is not present anymore:
    $this->drupalGet('test-media-bulk-form');
    $session->statusCodeEquals(200);
    $page->checkField('edit-media-bulk-form-0');
    $page->checkField('edit-media-bulk-form-1');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $session->statusCodeEquals(200);
    $session->fieldNotExists('Also delete the associated files?');
  }

}
