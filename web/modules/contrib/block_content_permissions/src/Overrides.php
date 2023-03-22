<?php

namespace Drupal\block_content_permissions;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Overrides for the block content permissions.
 */
class Overrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('views.view.block_content', $names)) {
      $overrides['views.view.block_content']['display']['default']['display_options']['title'] = 'Custom blocks';
      $overrides['views.view.block_content']['display']['default']['display_options']['access']['options']['perm'] = 'access block content overview';
      $overrides['views.view.block_content']['display']['page_1']['display_options']['path'] = 'admin/content/block-content';
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'Overrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
