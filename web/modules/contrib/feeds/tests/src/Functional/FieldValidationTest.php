<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\Component\Utility\Xss;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests fields validation.
 *
 * @group feeds
 */
class FieldValidationTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'filter',
  ];

  /**
   * Tests text field validation.
   */
  public function testTextFieldValidation() {
    $this->createFieldWithStorage('field_alpha', [
      'storage' => [
        'settings' => [
          'max_length' => 5,
        ],
      ],
    ]);

    // Create and configure feed type.
    $feed_type = $this->createFeedType([
      'parser' => 'csv',
      'custom_sources' => [
        'guid' => [
          'label' => 'guid',
          'value' => 'guid',
          'machine_name' => 'guid',
        ],
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
        'alpha' => [
          'label' => 'alpha',
          'value' => 'alpha',
          'machine_name' => 'alpha',
        ],
      ],
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'field_alpha',
          'map' => ['value' => 'alpha'],
        ],
      ]),
    ]);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/csv/content.csv',
    ]);
    $this->batchImport($feed);

    // Import CSV file.
    $page_text = Xss::filter($this->getSession()->getPage()->getContent(), []);
    $this->assertStringContainsString('Created 1 Article.', $page_text);
    $this->assertStringContainsString('Failed importing 1 Article.', $page_text);
    $this->assertStringContainsString('The content Ut wisi enim ad minim veniam failed to validate with the following errors', $page_text);
    $this->assertStringContainsString('field_alpha.0.value: field_alpha label: the text may not be longer than 5 characters.', $page_text);
  }

  /**
   * Tests if a field with admin filter format can be imported using the UI.
   *
   * When importing the body field using a filter format that anonymous users
   * are not allowed to use, import should not fail because of permission
   * issues.
   */
  public function testImportFieldWithAdminFilterFormatInUi() {
    // Create a body field for the article content type.
    node_add_body_field($this->nodeType);

    // Create a filter not available to anonymous users.
    $format = FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ]);
    $format->save();

    // Create an user that may use this format.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'administer filters',
      'access site reports',
      $format->getPermissionName(),
    ]);

    // Create a feed type, map to body field. Set the filter format that has
    // restricted access.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'body' => 'body',
    ], [
      'id' => 'my_feed_type',
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'body',
          'map' => ['value' => 'body'],
          'settings' => ['format' => $format->id()],
        ],
      ]),
    ]);

    // Create a feed that the admin user owns.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'uid' => $this->adminUser->id(),
    ]);

    // Create an user that can trigger the import.
    $account = $this->drupalCreateUser([
      'view my_feed_type feeds',
      'import my_feed_type feeds',
    ]);
    $this->drupalLogin($account);

    // And import!
    $this->drupalGet('feed/1/import');
    $this->submitForm([], 'Import');

    // Assert that 2 nodes have been created.
    $this->assertNodeCount(2);
    $this->assertSession()->pageTextContains('Created 2 Article items');
  }

  /**
   * Tests if a field with admin filter format can be imported on cron.
   *
   * When importing the body field using a filter format that anonymous users
   * are not allowed to use, import should not fail because of permission
   * issues.
   */
  public function testImportFieldWithAdminFilterFormatOnCron() {
    // Create a body field for the article content type.
    node_add_body_field($this->nodeType);

    // Create a filter not available to anonymous users.
    $format = FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ]);
    $format->save();

    // Create an user that may use this format.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'administer filters',
      'access site reports',
      $format->getPermissionName(),
    ]);
    $this->drupalLogin($this->adminUser);

    // Create a feed type, map to body field. Set the filter format that has
    // restricted access.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'body' => 'body',
    ], [
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'body',
          'map' => ['value' => 'body'],
          'settings' => ['format' => $format->id()],
        ],
      ]),
    ]);

    // Create a feed and import on cron.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
      'uid' => $this->adminUser->id(),
    ]);
    $feed->startCronImport();

    // Run cron to import.
    $this->cronRun();

    // Assert that 2 nodes have been created.
    $this->assertNodeCount(2);
  }

}
