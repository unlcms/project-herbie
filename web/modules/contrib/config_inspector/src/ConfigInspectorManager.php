<?php

namespace Drupal\config_inspector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Config\Schema\SchemaCheckTrait;

/**
 * Manages plugins for configuration translation mappers.
 */
class ConfigInspectorManager {

  use SchemaCheckTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a ConfigInspectorManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager) {
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config_manager;
  }

  /**
   * Provides definition of a configuration.
   *
   * @param string $plugin_id
   *   A string plugin ID.
   * @param bool $exception_on_invalid
   *   If TRUE, an invalid plugin ID will throw an exception.
   *
   * @return mixed|void
   *   Plugin definition. NULL if ID invalid and $exception_on_invalid FALSE.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->typedConfigManager->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * Checks if the configuration schema with the given config name exists.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return bool
   *   TRUE if configuration schema exists, FALSE otherwise.
   */
  public function hasSchema($name) {
    return $this->typedConfigManager->hasConfigSchema($name);
  }

  /**
   * Provides configuration data.
   *
   * @param string $name
   *   A string config key.
   *
   * @return array|null
   *   An associative array with configuration data.
   */
  public function getConfigData($name) {
    return $this->typedConfigManager->get($name)->getValue();
  }

  /**
   * Provides configuration schema.
   *
   * @param string $name
   *   A string config key.
   *
   * @return \Drupal\Core\TypedData\TraversableTypedDataInterface
   *   Typed configuration element.
   */
  public function getConfigSchema($name) {
    return $this->typedConfigManager->get($name);
  }

  /**
   * Gets all contained typed data properties as plain array.
   *
   * @param array|object $schema
   *   An array of config elements with key.
   *
   * @return array
   *   List of Element objects indexed by full name (keys with dot notation).
   */
  public function convertConfigElementToList($schema) {
    $list = [];
    foreach ($schema as $key => $element) {
      if ($element instanceof Element) {
        $list[$key] = $element;
        foreach ($this->convertConfigElementToList($element) as $sub_key => $value) {
          $list[$key . '.' . $sub_key] = $value;
        }
      }
      else {
        $list[$key] = $element;
      }
    }
    return $list;
  }

  /**
   * Check schema compliance in configuration object.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   *
   * @throws \Drupal\Core\Config\Schema\SchemaIncompleteException
   */
  public function checkValues($config_name) {
    $config_data = $this->configFactory->get($config_name)->get();
    return $this->checkConfigSchema($this->typedConfigManager, $config_name, $config_data);
  }

}
