<?php

namespace Drupal\feeds\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\State\CleanState;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginInterface;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Drupal\user\UserInterface;

/**
 * Defines the feed entity class.
 *
 * @ContentEntityType(
 *   id = "feeds_feed",
 *   label = @Translation("Feed"),
 *   bundle_label = @Translation("Feed type"),
 *   module = "feeds",
 *   handlers = {
 *     "storage" = "Drupal\feeds\FeedStorage",
 *     "view_builder" = "Drupal\feeds\FeedViewBuilder",
 *     "access" = "Drupal\feeds\FeedAccessControlHandler",
 *     "views_data" = "Drupal\feeds\FeedViewsData",
 *     "form" = {
 *       "default" = "Drupal\feeds\FeedForm",
 *       "update" = "Drupal\feeds\FeedForm",
 *       "delete" = "Drupal\feeds\Form\FeedDeleteForm",
 *       "import" = "Drupal\feeds\Form\FeedImportForm",
 *       "schedule_import" = "Drupal\feeds\Form\FeedScheduleImportForm",
 *       "clear" = "Drupal\feeds\Form\FeedClearForm",
 *       "unlock" = "Drupal\feeds\Form\FeedUnlockForm",
 *     },
 *     "list_builder" = "Drupal\feeds\FeedListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "feed_import" = "Drupal\feeds\FeedImportHandler",
 *     "feed_clear" = "Drupal\feeds\FeedClearHandler",
 *     "feed_expire" = "Drupal\feeds\FeedExpireHandler"
 *   },
 *   base_table = "feeds_feed",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "fid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   permission_granularity = "bundle",
 *   bundle_entity_type = "feeds_feed_type",
 *   field_ui_base_route = "entity.feeds_feed_type.edit_form",
 *   links = {
 *     "canonical" = "/feed/{feeds_feed}",
 *     "add-page" = "/feed/add",
 *     "add-form" = "/feed/add/{feeds_feed_type}",
 *     "delete-form" = "/feed/{feeds_feed}/delete",
 *     "edit-form" = "/feed/{feeds_feed}/edit",
 *     "import-form" = "/feed/{feeds_feed}/import",
 *     "schedule-import-form" = "/feed/{feeds_feed}/schedule-import",
 *     "clear-form" = "/feed/{feeds_feed}/delete-items",
 *     "unlock" = "/feed/{feeds_feed}/unlock",
 *   }
 * )
 */
class Feed extends ContentEntityBase implements FeedInterface {

  use EntityChangedTrait;

  /**
   * An array of import stage states keyed by state.
   *
   * @var array
   */
  protected $states;

  /**
   * Implements the magic __wakeup function to reset states.
   */
  public function __wakeup() {
    $this->states = [];
  }

  /**
   * Gets the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher service.
   */
  protected function eventDispatcher() {
    return \Drupal::service('event_dispatcher');
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return (int) $this->get('fid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    return $this->set('source', $source);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', (int) $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return (int) $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportedTime() {
    return (int) $this->get('imported')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextImportTime() {
    return (int) $this->get('next')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueuedTime() {
    return (int) $this->get('queued')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueuedTime($queued) {
    $this->set('queued', (int) $queued);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $type = $this->get('type')->entity;
    if (empty($type)) {
      if ($this->id()) {
        throw new EntityStorageException(strtr('The feed type "@type" for feed @id no longer exists.', [
          '@type' => $this->bundle(),
          '@id' => $this->id(),
        ]));
      }
      else {
        throw new EntityStorageException(strtr('The feed type "@type" no longer exists.', [
          '@type' => $this->bundle(),
        ]));
      }
    }
    return $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', $active ? static::ACTIVE : static::INACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function import() {
    $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->import($this);
  }

  /**
   * {@inheritdoc}
   */
  public function startBatchImport() {
    $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->startBatchImport($this);
  }

  /**
   * {@inheritdoc}
   */
  public function startCronImport() {
    $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->startCronImport($this);
  }

  /**
   * {@inheritdoc}
   */
  public function pushImport($raw) {
    return $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->pushImport($this, $raw);
  }

  /**
   * {@inheritdoc}
   */
  public function startBatchClear() {
    $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_clear')
      ->startBatchClear($this);
  }

  /**
   * {@inheritdoc}
   */
  public function startBatchExpire() {
    return $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_expire')
      ->startBatchExpire($this);
  }

  /**
   * {@inheritdoc}
   */
  public function hasQueueTasks(): bool {
    return $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->hasQueueTasks($this);
  }

  /**
   * {@inheritdoc}
   */
  public function clearQueueTasks(): void {
    $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->clearQueueTasks($this);
  }

  /**
   * {@inheritdoc}
   */
  public function hasRecentProgress(int $seconds = 3600): bool {
    return $this->entityTypeManager()
      ->getHandler('feeds_feed', 'feed_import')
      ->hasRecentProgress($this, $seconds);
  }

  /**
   * {@inheritdoc}
   */
  public function dispatchEntityEvent($event, EntityInterface $entity, ItemInterface $item) {
    return $this->eventDispatcher()->dispatch(new EntityEvent($this, $entity, $item), $event);
  }

  /**
   * {@inheritdoc}
   */
  public function finishImport() {
    $time = time();

    $this->getType()
      ->getProcessor()
      ->postProcess($this, $this->getState(StateInterface::PROCESS));

    foreach ($this->states as $state) {
      if (is_object($state)) {
        $state->displayMessages();
        $state->logMessages($this);
      }
    }

    // Allow other modules to react upon finishing importing.
    $this->eventDispatcher()->dispatch(new ImportFinishedEvent($this), FeedsEvents::IMPORT_FINISHED);

    $this->set('imported', $time);

    $interval = $this->getType()->getImportPeriod();
    if ($interval !== FeedTypeInterface::SCHEDULE_NEVER) {
      $this->set('next', $interval + $time);
    }

    $this->unlock();
    $this->save();
  }

  /**
   * Cleans up after an import.
   */
  public function finishClear() {
    $this
      ->getType()
      ->getProcessor()
      ->postClear($this, $this->getState(StateInterface::CLEAR));

    foreach ($this->states as $state) {
      is_object($state) ? $state->displayMessages() : NULL;
    }

    $this->clearStates();
  }

  /**
   * {@inheritdoc}
   */
  public function getState($stage) {
    if (!isset($this->states[$stage])) {
      $state = \Drupal::keyValue('feeds_feed.' . $this->id())->get($stage);

      if (empty($state)) {
        // @todo move this logic to a factory or alike.
        switch ($stage) {
          case StateInterface::CLEAN:
            $state = new CleanState($this->id());
            break;

          default:
            $state = new State();
            break;
        }
      }

      $this->states[$stage] = $state;
    }
    return $this->states[$stage];
  }

  /**
   * {@inheritdoc}
   */
  public function setState($stage, StateInterface $state = NULL) {
    $this->states[$stage] = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function clearStates() {
    $this->states = [];
    \Drupal::keyValue('feeds_feed.' . $this->id())->deleteAll();

    // Clean up references in feeds_clean_list table for this feed.
    \Drupal::database()->delete(CleanState::TABLE_NAME)
      ->condition('feed_id', $this->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function saveStates() {
    \Drupal::keyValue('feeds_feed.' . $this->id())->setMultiple($this->states);
    \Drupal::keyValue('feeds_feed.' . $this->id())->set('last_activity', time());
  }

  /**
   * {@inheritdoc}
   */
  public function progressFetching() {
    return $this->getState(StateInterface::FETCH)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressParsing() {
    return $this->getState(StateInterface::PARSE)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressImporting() {
    $fetcher = $this->getState(StateInterface::FETCH);
    $parser = $this->getState(StateInterface::PARSE);

    if ($fetcher->progress === StateInterface::BATCH_COMPLETE && $parser->progress === StateInterface::BATCH_COMPLETE) {
      return StateInterface::BATCH_COMPLETE;
    }
    // Fetching envelops parsing.
    // @todo This assumes all fetchers neatly use total. May not be the case.
    $fetcher_fraction = $fetcher->total ? 1.0 / $fetcher->total : 1.0;
    $parser_progress = $parser->progress * $fetcher_fraction;
    $result = $fetcher->progress - $fetcher_fraction + $parser_progress;

    if ($result >= StateInterface::BATCH_COMPLETE) {
      return 0.99;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function progressCleaning() {
    return $this->getState(StateInterface::CLEAN)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressClearing() {
    return $this->getState(StateInterface::CLEAR)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function progressExpiring() {
    return $this->getState(StateInterface::EXPIRE)->progress;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount() {
    return (int) $this->get('item_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function lock() {
    $timeout = \Drupal::config('feeds.settings')->get('lock_timeout') ?? 3600 * 12;
    if (!\Drupal::service('feeds.lock')->acquire("feeds_feed:{$this->id()}", $timeout)) {
      $args = ['@id' => $this->bundle(), '@fid' => $this->id()];
      throw new LockException(new FormattableMarkup('Cannot acquire lock for feed @id / @fid.', $args));
    }
    Cache::invalidateTags(['feeds_feed_locked']);
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    \Drupal::service('feeds.lock')->release("feeds_feed:{$this->id()}");
    Cache::invalidateTags(['feeds_feed_locked']);

    // Clean up import states and stale queue tasks.
    $this->clearStates();
    $this->setQueuedTime(0);
    $this->clearQueueTasks();
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !\Drupal::service('feeds.lock')->lockMayBeAvailable("feeds_feed:{$this->id()}");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFor(FeedsPluginInterface $client) {
    $type = $client->pluginType();
    // @todo Figure out why for the UploadFetcher there is no config available.
    $data = $this->get('config')->$type;
    $data = !empty($data) ? $data : [];

    return $data + $client->defaultFeedConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationFor(FeedsPluginInterface $client, array $configuration) {
    $type = $client->pluginType();
    $this->get('config')->$type = array_intersect_key($configuration, $client->defaultFeedConfiguration()) + $client->defaultFeedConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    $feed_type = $this->getType();

    foreach ($feed_type->getPlugins() as $plugin) {
      $plugin->onFeedSave($this, $update);
    }

    // If this is a new node, 'next' and 'imported' will be zero which will
    // queue it for the next run.
    if ($feed_type->getImportPeriod() === FeedTypeInterface::SCHEDULE_NEVER) {
      $this->set('next', FeedTypeInterface::SCHEDULE_NEVER);
    }

    // Update the item count.
    $this->set('item_count', $feed_type->getProcessor()->getItemCount($this));
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage_controller, array $feeds) {
    // Delete values from other tables also referencing these feeds.
    $ids = array_keys($feeds);

    // Group feeds by type.
    $grouped = [];
    foreach ($feeds as $fid => $feed) {
      $grouped[$feed->bundle()][$fid] = $feed;
    }

    // Alert plugins that we are deleting.
    foreach ($grouped as $group) {
      // Grab the first feed to get its type.
      $feed = reset($group);
      try {
        // Clear all state objects for the feed.
        $feed->clearStates();

        foreach ($feed->getType()->getPlugins() as $plugin) {
          $plugin->onFeedDeleteMultiple($group);
        }
      }
      catch (EntityStorageException $e) {
        // Ignore the case where the feed type no longer exists, but do log an
        // error.
        $args = [
          '%title' => $feed->label(),
          '@error' => $e->getMessage(),
        ];
        \Drupal::logger('feeds')->warning('Could not perform some post cleanups for feed %title because of the following error: @error', $args);
      }
    }

    // Clean up references in feeds_clean_list table for each feed.
    \Drupal::database()->delete(CleanState::TABLE_NAME)
      ->condition('feed_id', $ids, 'IN')
      ->execute();

    \Drupal::service('event_dispatcher')->dispatch(new DeleteFeedsEvent($feeds), FeedsEvents::FEEDS_DELETE);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Feed ID'))
      ->setDescription(t('The feed ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The feed UUID.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Feed type'))
      ->setDescription(t('The feed type.'))
      ->setSetting('target_type', 'feeds_feed_type')
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of this feed, always treated as non-markup plain text.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the feed author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\feeds\Entity\Feed::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Importing status'))
      ->setDescription(t('A boolean indicating whether the feed is active.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the feed was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the feed was last edited.'));

    $fields['imported'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last import'))
      ->setDescription(t('The time that the feed was imported.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp_ago',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['next'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next import'))
      ->setDescription(t('The time that the feed will import next.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['queued'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Queued'))
      ->setDescription(t('Time when this feed was queued for refresh, 0 if not queued.'))
      ->setDefaultValue(0);

    $fields['source'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Source'))
      ->setDescription(t('The source of the feed.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'feeds_uri_link',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['config'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Config'))
      ->setDescription(t('The config of the feed.'));

    $fields['item_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Items imported'))
      ->setDescription(t('The number of items imported.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
