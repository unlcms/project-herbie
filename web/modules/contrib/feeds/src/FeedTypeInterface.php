<?php

namespace Drupal\feeds;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a feeds feed type entity.
 *
 * A feed type is a wrapper around a set of configured plugins that are used to
 * perform an import. The feed type manages the configuration on behalf of the
 * plugins.
 */
interface FeedTypeInterface extends ConfigEntityInterface {

  /**
   * Indicates that a feed should never be scheduled.
   */
  const SCHEDULE_NEVER = -1;

  /**
   * Indicates that a feed should be imported as often as possible.
   */
  const SCHEDULE_CONTINUOUSLY = 0;

  /**
   * Returns the description of the feed type.
   *
   * @return string
   *   The description of the feed type.
   */
  public function getDescription();

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this feed type.
   */
  public function getHelp();

  /**
   * Returns the import period.
   *
   * @return int
   *   The import period in seconds.
   */
  public function getImportPeriod();

  /**
   * Sets the import period.
   *
   * @param int $import_period
   *   The import period in seconds.
   */
  public function setImportPeriod($import_period);

  /**
   * Returns the configured plugins for this feed type.
   *
   * @return \Drupal\feeds\Plugin\Type\PluginBase[]
   *   An array of plugins keyed by plugin type.
   */
  public function getPlugins();

  /**
   * Returns the configured fetcher for this feed type.
   *
   * @return \Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface
   *   The fetcher associated with this feed type.
   */
  public function getFetcher();

  /**
   * Returns the configured parser for this feed type.
   *
   * @return \Drupal\feeds\Plugin\Type\Parser\ParserInterface
   *   The parser associated with this feed type.
   */
  public function getParser();

  /**
   * Returns the configured processor for this feed type.
   *
   * @return \Drupal\feeds\Plugin\Type\Processor\ProcessorInterface
   *   The processor associated with this feed type.
   */
  public function getProcessor();

  /**
   * Returns the target plugin for the given target.
   *
   * @param int $delta
   *   The number under which this target is indexed in the mappings.
   *
   * @return \Drupal\feeds\Plugin\Type\Target\TargetInterface
   *   A target plugin instance.
   */
  public function getTargetPlugin($delta);

  /**
   * Returns the source plugin for the given source if it exists.
   *
   * @param string $source
   *   The source to get a plugin for.
   *
   * @return \Drupal\feeds\Plugin\Type\Source\SourceInterface|false
   *   A source plugin instance, if there is one. False otherwise.
   */
  public function getSourcePlugin($source);

  /**
   * Returns the mapping sources for this feed type.
   *
   * @return array
   *   An array of mapping sources.
   */
  public function getMappingSources();

  /**
   * Returns the mapping targets for this feed type.
   *
   * @return array
   *   An array of mapping targets.
   */
  public function getMappingTargets();

  /**
   * Returns the mappings for this feed type.
   *
   * @return array
   *   The list of mappings.
   */
  public function getMappings();

  /**
   * Sets the mappings for the feed type.
   *
   * @param array $mappings
   *   A list of mappings.
   */
  public function setMappings(array $mappings);

  /**
   * Adds a mapping to the feed type.
   *
   * @param array $mapping
   *   A single mapping.
   */
  public function addMapping(array $mapping);

  /**
   * Removes a mapping from the feed type.
   *
   * @param int $delta
   *   The mapping delta to remove.
   *
   * @return $this
   *   An instance of this class.
   */
  public function removeMapping($delta);

  /**
   * Removes all mappings.
   *
   * @return $this
   *   An instance of this class.
   */
  public function removeMappings();

  /**
   * Returns a list of mapped sources.
   *
   * @return string[]
   *   A list of mapped source names.
   */
  public function getMappedSources();

  /**
   * Adds a custom source that can be used in mapping.
   *
   * @param string $name
   *   The unique name for the source.
   * @param array $source
   *   An array of the source properties:
   *   - label
   *     A human readable name.
   *   - value
   *     The value to extract from the feed.
   *   - description
   *     (optional) A description of the source.
   *
   * @return $this
   *   An instance of this class.
   */
  public function addCustomSource($name, array $source);

  /**
   * Gets a custom a source.
   *
   * @param string $name
   *   The name of the custom source to get.
   *
   * @return array|null
   *   The properties of the custom source:
   *   - label
   *     A human readable name.
   *   - value
   *     The value to extract from the feed.
   *   - description
   *     (optional) A description of the source.
   *   Null if the custom source doesn't exist.
   */
  public function getCustomSource($name);

  /**
   * Gets all custom sources.
   *
   * @param array $types
   *   (optional) The type of custom sources to get.
   *
   * @return array
   *   A list of custom source definitions.
   */
  public function getCustomSources(array $types = []);

  /**
   * Returns if a custom source already exists.
   *
   * @param string $name
   *   The source's machine name to check for existence.
   *
   * @return bool
   *   True if the source exists, false otherwise.
   */
  public function customSourceExists($name);

  /**
   * Removes a custom a source.
   *
   * @param string $name
   *   The name of the custom source to delete.
   *
   * @return $this
   *   An instance of this class.
   */
  public function removeCustomSource($name);

  /**
   * Returns whether the feed type is considered locked.
   *
   * @return bool
   *   True if locked, false if not.
   */
  public function isLocked();

}
