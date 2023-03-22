<?php

namespace Drupal\Tests\feeds\Traits;

use Drupal\feeds\Entity\Feed;
use Drupal\feeds\Entity\FeedType;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;

/**
 * Provides methods to create feeds and feed types with default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedCreationTrait {

  /**
   * Creates a feed type with default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the feed type entity.
   *   The following defaults are provided:
   *   - label: Random string.
   *   - ID: Random string.
   *   - import_period: never.
   *   - processor_configuration: authorize off and article bundle.
   *   - mappings: mapping to guid and title.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   The created feed type entity.
   */
  protected function createFeedType(array $settings = []) {
    // Populate default array.
    $settings += [
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'import_period' => FeedTypeInterface::SCHEDULE_NEVER,
      'processor_configuration' => [
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ],
      'mappings' => $this->getDefaultMappings(),
    ];

    $feed_type = FeedType::create($settings);
    $feed_type->save();

    return $feed_type;
  }

  /**
   * Creates a feed type for the CSV parser.
   *
   * @param array $columns
   *   The CSV columns, keyed by machine name.
   * @param array $settings
   *   (optional) An associative array of settings for the feed type entity.
   *   The following defaults are provided:
   *   - label: Random string.
   *   - ID: Random string.
   *   - import_period: never.
   *   - processor_configuration: authorize off and article bundle.
   *   - mappings: mapping to guid and title.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   The created feed type entity.
   */
  protected function createFeedTypeForCsv(array $columns, array $settings = []) {
    $sources = [];
    foreach ($columns as $machine_name => $column) {
      $sources[$machine_name] = [
        'label' => $column,
        'value' => $column,
        'machine_name' => $machine_name,
      ];
    }

    if (!isset($settings['custom_sources'])) {
      $settings['custom_sources'] = $sources;
    }
    else {
      $settings['custom_sources'] += $sources;
    }

    $settings += [
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'csv',
      ],
      'parser' => 'csv',
    ];

    return $this->createFeedType($settings);
  }

  /**
   * Returns default mappings for tests.
   *
   * Can be overridden by specific tests.
   *
   * @return array
   *   A list of default mappings.
   */
  protected function getDefaultMappings() {
    return [
      [
        'target' => 'feeds_item',
        'map' => ['guid' => 'guid'],
        'unique' => ['guid' => TRUE],
        'settings' => [],
      ],
      [
        'target' => 'title',
        'map' => ['value' => 'title'],
        'unique' => [],
        'settings' => [
          'language' => NULL,
        ],
      ],
    ];
  }

  /**
   * Creates a feed with default settings.
   *
   * @param string $feed_type_id
   *   The feed type ID.
   * @param array $settings
   *   (optional) An associative array of settings for the feed entity.
   *   The following defaults are provided:
   *   - title: Random string.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The created feed entity.
   */
  protected function createFeed($feed_type_id, array $settings = []) {
    // Populate default array.
    $settings += [
      'title' => $this->randomMachineName(),
    ];
    $settings['type'] = $feed_type_id;

    $feed = Feed::create($settings);
    $feed->save();

    return $feed;
  }

  /**
   * Reloads a feed entity.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed entity to reload.
   *
   * @return \Drupal\feeds\FeedInterface
   *   The reloaded feed.
   */
  protected function reloadFeed(FeedInterface $feed) {
    /** @var \Drupal\feeds\FeedStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('feeds_feed');
    return $storage->load($feed->id());
  }

}
