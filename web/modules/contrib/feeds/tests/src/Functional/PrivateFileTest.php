<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\file\Functional\FileFieldTestBase;

/**
 * Tests private files work with the Feeds module.
 *
 * @group feeds
 */
class PrivateFileTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'file_module_test',
    'field_ui',
    'feeds',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This test expects unused managed files to be marked as a temporary file.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
  }

  /**
   * Tests private files work with the Feeds module.
   *
   * @see feeds_file_download()
   */
  public function testPrivateFile() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name, ['uri_scheme' => 'private']);

    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name, TRUE, ['private' => TRUE]);
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache([$nid]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    // Ensure the file can be viewed.
    $this->drupalGet('node/' . $node->id());
    // File reference is displayed after attaching it.
    $this->assertSession()->responseContains($node_file->getFilename());
    // Ensure the file can be downloaded.
    $this->drupalGet($node_file->createFileUrl());
    // Confirmed that the generated URL is correct by downloading the shipped
    // file.
    $this->assertSession()->statusCodeEquals(200);
  }

}
