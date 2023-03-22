<?php

namespace Drupal\feeds_test_extra_sources\Feeds\Source;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Source\SourceBase;

/**
 * A source exposing site config.
 *
 * @FeedsSource(
 *   id = "site"
 * )
 */
class SiteSource extends SourceBase {

  /**
   * {@inheritdoc}
   */
  public static function sources(array &$sources, FeedTypeInterface $feed_type, array $definition) {
    $sources['site:name'] = [
      'label' => t('Site name'),
      'id' => $definition['id'],
    ];
    $sources['site:mail'] = [
      'label' => t('Site mail'),
      'id' => $definition['id'],
    ];
    $sources['site:slogan'] = [
      'label' => t('Site slogan'),
      'id' => $definition['id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElement(FeedInterface $feed, ItemInterface $item) {
    list(, $field_name) = explode(':', $this->configuration['source']);

    return \Drupal::config('system.site')->get($field_name);
  }

}
