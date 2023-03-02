<?php

namespace Drupal\Tests\media_embed_view_mode_restrictions\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Base test class.
 */
abstract class MediaEmbedViewModeRestrictionsTestBase extends WebDriverTestBase {

  use MediaTypeCreationTrait;

  /**
   * User object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'filter',
    'node',
    'field_ui',
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createMediaType('image', [
      'id' => 'image_bundle_1',
      'label' => 'Image Bundle 1',
    ]);
    $this->createMediaType('image', [
      'id' => 'image_bundle_2',
      'label' => 'Image Bundle 2',
    ]);
    $this->createMediaType('file', [
      'id' => 'file',
      'label' => 'File',
    ]);

    EntityViewMode::create([
      'id' => 'media.viewmode1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View mode 1',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.viewmode2',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View mode 2',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.viewmode3',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View mode 3',
    ])->save();

    $format = FilterFormat::create([
      'format' => 'media_embed_test',
      'name' => 'Test format',
      'filters' => [],
    ]);
    $format->save();

    $this->user = $this->drupalCreateUser([
      'administer filters',
      $format->getPermissionName(),
    ]);

    $this->drupalLogin($this->user);
  }

}
