<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\feeds\Feeds\Target\Image;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Image
 * @group feeds
 */
class ImageTest extends FileTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetPluginClass() {
    return Image::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetDefinition() {
    $method = $this->getMethod(Image::class, 'prepareTarget')->getClosure();
    $field_definition_mock = $this->getMockFieldDefinition([
      'display_field' => 'false',
      'display_default' => 'false',
      'uri_scheme' => 'public',
      'target_type' => 'file',
      'file_directory' => '[date:custom:Y]-[date:custom:m]',
      'file_extensions' => 'png gif jpg jpeg',
      'max_filesize' => '',
      'max_resolution' => '',
      'min_resolution' => '',
      'alt_field' => 'true',
      'title_field' => 'true',
      'alt_field_required' => 'true',
      'title_field_required' => 'true',
      'default_image' => [
        'uuid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ],
      'handler' => 'default:file',
      'handler_settings' => [],
    ]);

    return $method($field_definition_mock);
  }

}
