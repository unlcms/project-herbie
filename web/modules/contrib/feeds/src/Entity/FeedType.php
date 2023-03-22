<?php

namespace Drupal\feeds\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\feeds\Exception\MissingTargetException;
use Drupal\feeds\Feeds\FeedsSingleLazyPluginCollection;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\DependentWithRemovalPluginInterface;
use Drupal\feeds\Plugin\Type\LockableInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Defines the Feeds feed type entity.
 *
 * @ConfigEntityType(
 *   id = "feeds_feed_type",
 *   label = @Translation("Feed type"),
 *   module = "feeds",
 *   handlers = {
 *     "access" = "Drupal\feeds\FeedTypeAccessControlHandler",
 *     "list_builder" = "Drupal\feeds\FeedTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\feeds\FeedTypeForm",
 *       "create" = "Drupal\feeds\FeedTypeForm",
 *       "edit" = "Drupal\feeds\FeedTypeForm",
 *       "delete" = "Drupal\feeds\Form\FeedTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "feed_type",
 *   bundle_of = "feeds_feed",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "help",
 *     "import_period",
 *     "fetcher",
 *     "fetcher_configuration",
 *     "parser",
 *     "parser_configuration",
 *     "processor",
 *     "processor_configuration",
 *     "custom_sources",
 *     "mappings"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/feeds",
 *     "add-form" = "/admin/structure/feeds/add",
 *     "edit-form" = "/admin/structure/feeds/manage/{feeds_feed_type}",
 *     "mapping" = "/admin/structure/feeds/manage/{feeds_feed_type}/mapping",
 *     "delete-form" = "/admin/structure/feeds/manage/{feeds_feed_type}/delete"
 *   },
 *   admin_permission = "administer feeds"
 * )
 */
class FeedType extends ConfigEntityBundleBase implements FeedTypeInterface, EntityWithPluginCollectionInterface {

  /**
   * The feed type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the feed type.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of the feed type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a Feed of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * The import period.
   *
   * @var int
   */
  protected $import_period = 3600;

  /**
   * The types of plugins we support.
   *
   * @var array
   *
   * @todo Make this dynamic?
   */
  protected $pluginTypes = ['fetcher', 'parser', 'processor'];

  /**
   * The fetcher plugin id.
   *
   * @var string
   */
  protected $fetcher = 'http';

  /**
   * The parser plugin id.
   *
   * @var string
   */
  protected $parser = 'syndication';

  /**
   * The processor plugin id.
   *
   * @var string
   */
  protected $processor = 'entity:node';

  /**
   * The fetcher plugin configuration.
   *
   * @var array
   */
  protected $fetcher_configuration = [];

  /**
   * The parser plugin configuration.
   *
   * @var array
   */
  protected $parser_configuration = [];

  /**
   * The processor plugin configuration.
   *
   * @var array
   */
  protected $processor_configuration = [];

  /**
   * The list of source to target mappings.
   *
   * @var array
   */
  protected $mappings = [];

  /**
   * The list of custom sources.
   *
   * @var array
   */
  protected $custom_sources = [];

  /**
   * The list of sources.
   *
   * @var array
   */
  protected $sources;

  /**
   * The list of targets.
   *
   * @var array
   */
  protected $targets;

  /**
   * The plugin collections that store feeds plugins keyed by plugin type.
   *
   * These are lazily instantiated on-demand.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection[]
   */
  protected $pluginCollection;

  /**
   * The instantiated target plugins.
   *
   * @var \Drupal\feeds\Plugin\Type\Target\TargetInterface[]
   */
  protected $targetPlugins = [];

  /**
   * The instantiated source plugins.
   *
   * @var \Drupal\feeds\Plugin\Type\Target\SourceInterface[]
   */
  protected $sourcePlugins = [];

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = parent::__sleep();

    // Do not serialize pluginCollection as this can contain a
    // \Drupal\Core\Entity\EntityType instance which can contain a
    // stringTranslation object that is not serializable.
    // @see https://www.drupal.org/project/drupal/issues/2893029
    $vars = array_flip($vars);
    unset($vars['pluginCollection']);
    $vars = array_flip($vars);

    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Remove mappings when processor changes.
    if ($property_name === 'processor' && $this->processor !== $value) {
      $this->removeMappings();
    }
    return parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    foreach ($this->getPlugins() as $plugin) {
      if ($plugin instanceof LockableInterface && $plugin->isLocked()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportPeriod() {
    return $this->import_period;
  }

  /**
   * {@inheritdoc}
   */
  public function setImportPeriod($import_period) {
    $this->import_period = (int) $import_period;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    if ($this->sources === NULL) {
      $this->sources = $this->getParser()->getMappingSources();
      $definitions = $this->getSourcePluginManager()->getDefinitions();

      foreach ($definitions as $definition) {
        $class = $definition['class'];
        $class::sources($this->sources, $this, $definition);
      }

      $this->alter('feeds_sources', $this->sources);
    }

    return $this->sources + $this->custom_sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTargets() {
    if ($this->targets === NULL) {
      $this->targets = [];
      $definitions = \Drupal::service('plugin.manager.feeds.target')->getDefinitions();

      foreach ($definitions as $definition) {
        $class = $definition['class'];
        $class::targets($this->targets, $this, $definition);
      }

      \Drupal::moduleHandler()->alter('feeds_targets', $this->targets, $this);
    }

    return $this->targets;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappings() {
    return $this->mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappings(array $mappings) {
    $this->mappings = $mappings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addMapping(array $mapping) {
    $this->mappings[] = $mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMapping($delta) {
    unset($this->mappings[$delta]);
    unset($this->targetPlugins[$delta]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMappings() {
    $this->mappings = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedSources() {
    $sources = [];

    foreach ($this->getMappings() as $mapping) {
      foreach ($mapping['map'] as $column => $source) {
        if ($source === '') {
          // Skip empty sources.
          continue;
        }
        $sources[$source] = $source;
      }
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function addCustomSource($name, array $source) {
    $this->custom_sources[$name] = $source;
    $this->custom_sources[$name]['machine_name'] = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomSource($name) {
    if (!isset($this->custom_sources[$name])) {
      return NULL;
    }
    return $this->custom_sources[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomSources(array $types = []) {
    if (empty($types)) {
      return $this->custom_sources;
    }

    $return = [];
    foreach ($this->custom_sources as $key => $source) {
      if (!isset($source['type'])) {
        // No type specified. Just return this one.
        $return[$key] = $source;
      }
      elseif (in_array($source['type'], $types)) {
        $return[$key] = $source;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function customSourceExists($name) {
    return isset($this->custom_sources[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function removeCustomSource($name) {
    unset($this->custom_sources[$name]);

    // If the custom source is currently mapped, remove it from there as well.
    foreach ($this->mappings as $delta => $mapping) {
      foreach ($mapping['map'] as $key => $value) {
        if ($value == $name) {
          $this->mappings[$delta]['map'][$key] = '';
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    $plugins = [];
    foreach ($this->pluginTypes as $type) {
      $plugins[$type] = $this->getPlugin($type);
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getFetcher() {
    return $this->getPlugin('fetcher');
  }

  /**
   * {@inheritdoc}
   */
  public function getParser() {
    return $this->getPlugin('parser');
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor() {
    return $this->getPlugin('processor');
  }

  /**
   * Returns the configured plugin for this type given the plugin type.
   *
   * @param string $plugin_type
   *   The plugin type to return.
   *
   * @return \Drupal\feeds\Plugin\PluginInterface
   *   The plugin specified.
   */
  protected function getPlugin($plugin_type) {
    $bags = $this->getPluginCollections();

    return $bags[$plugin_type . '_configuration']->get($this->$plugin_type);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Use plugin bag.
   */
  public function getTargetPlugin($delta) {
    if (isset($this->targetPlugins[$delta])) {
      return $this->targetPlugins[$delta];
    }

    $targets = $this->getMappingTargets();
    $target = $this->mappings[$delta]['target'];

    // Make sure that the target exists.
    if (!isset($targets[$target])) {
      // The target is missing!
      throw new MissingTargetException(sprintf('The Feeds target "%s" does not exist.', $target));
    }

    // The target is a plugin.
    $id = $targets[$target]->getPluginId();

    $configuration = [];
    $configuration['feed_type'] = $this;
    $configuration['target_definition'] = $targets[$target];
    if (isset($this->mappings[$delta]['settings'])) {
      $configuration += $this->mappings[$delta]['settings'];
    }
    $this->targetPlugins[$delta] = \Drupal::service('plugin.manager.feeds.target')->createInstance($id, $configuration);

    return $this->targetPlugins[$delta];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Use plugin bag.
   */
  public function getSourcePlugin($source) {
    if (!isset($this->sourcePlugins[$source])) {
      $sources = $this->getMappingSources();

      // The source is a plugin.
      if (isset($sources[$source]['id'])) {
        $configuration = [
          'feed_type' => $this,
          'source' => $source,
        ];
        $this->sourcePlugins[$source] = $this->getSourcePluginManager()->createInstance($sources[$source]['id'], $configuration);
      }
      else {
        $this->sourcePlugins[$source] = FALSE;
      }
    }

    return $this->sourcePlugins[$source];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginOptionsList($plugin_type) {
    $manager = \Drupal::service("plugin.manager.feeds.$plugin_type");

    $options = [];
    foreach ($manager->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['title'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    if (!isset($this->pluginCollection)) {
      $this->pluginCollection = [];
      foreach ($this->pluginTypes as $type) {
        $this->pluginCollection[$type . '_configuration'] = new FeedsSingleLazyPluginCollection(
          \Drupal::service("plugin.manager.feeds.$type"),
          $this->get($type),
          $this->get($type . '_configuration'),
          $this
        );
      }
    }

    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return [
      'path' => 'admin/structure/feeds/manage/' . $this->id(),
      'options' => [
        'entity_type' => $this->entityType,
        'entity' => $this,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    foreach ($this->getPlugins() as $type => $plugin) {
      $plugin->onFeedTypeSave($update);
    }

    foreach ($this->targetPlugins as $delta => $target_plugin) {
      if ($target_plugin instanceof ConfigurableTargetInterface) {
        $this->mappings[$delta]['settings'] = $target_plugin->getConfiguration();
      }
      else {
        unset($this->mappings[$delta]['settings']);
      }
    }

    $this->mappings = array_values($this->mappings);
    parent::preSave($storage_controller, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      // Clear the queue worker plugin cache so that our derivatives will be
      // found.
      \Drupal::service('plugin.manager.queue_worker')->clearCachedDefinitions();
      \Drupal::queue('feeds_feed_refresh:' . $this->id())->createQueue();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    foreach ($entities as $entity) {
      foreach ($entity->getPlugins() as $plugin) {
        $plugin->onFeedTypeDelete();
      }

      // Delete any existing queues related to this type.
      if ($queue = \Drupal::queue('feeds_feed_refresh:' . $entity->id())) {
        $queue->deleteQueue();
      }
    }

    // Clear the queue worker plugin cache to remove this derivative.
    \Drupal::service('plugin.manager.queue_worker')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    $properties['mappings'] = $this->mappings;
    return $properties;
  }

  /**
   * Returns the source plugin manager.
   *
   * @return \Drupal\feeds\Plugin\Type\FeedsPluginManager
   *   The source plugin manager.
   */
  protected function getSourcePluginManager() {
    return \Drupal::service('plugin.manager.feeds.source');
  }

  /**
   * Wrapper around \Drupal\Core\Extension\ModuleHandlerInterface::alter().
   *
   * @param string|array $type
   *   A string describing the type of the alterable $data or an array if
   *   hook_TYPE_alter() needs to be invoked for each value in the array.
   * @param mixed $data
   *   The variable to be altered.
   *
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::alter()
   */
  protected function alter($type, &$data) {
    return \Drupal::moduleHandler()->alter($type, $data, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Calculate plugin dependencies for each target plugin.
    // @todo support other plugin types as well.
    foreach ($this->getMappings() as $delta => $mapping) {
      try {
        $plugin = $this->getTargetPlugin($delta);
        $this->calculatePluginDependencies($plugin);
      }
      catch (MissingTargetException $e) {
        // Log an error when a target is not found.
        watchdog_exception('feeds', $e);
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    // Don't intervene if the feeds module is removed.
    if (isset($dependencies['module']) && in_array('feeds', $dependencies['module'])) {
      return FALSE;
    }

    // Check each target plugin for if they want to do something on dependency
    // removal.
    foreach ($this->getMappings() as $delta => $mapping) {
      $plugin = $this->getTargetPlugin($delta);
      if ($plugin instanceof DependentWithRemovalPluginInterface) {
        if ($plugin->onDependencyRemoval($dependencies)) {
          $this->removeMapping($delta);
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      // Force a recalculation of the dependencies if we made changes.
      $this->calculateDependencies();
    }

    return $changed;
  }

}
