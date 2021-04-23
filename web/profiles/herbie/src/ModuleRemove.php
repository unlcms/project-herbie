<?php

namespace Drupal\herbie;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * Removes missing modules.
 */
class ModuleRemove {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * Removes missing modules.
   *
   * @param string $module_name
   *   The name of the missing module to be removed.
   */
  public function remove($module_name) {
    // Remove from core.extensions config.
    $extensions = $this->configFactory->getEditable('core.extension')->get();
    unset($extensions['module'][$module_name]);
    $this->configFactory->getEditable('core.extension')->setData($extensions);
    $this->configFactory->getEditable('core.extension')->save();

    // Clean up module's configuration (assumes correct name spacing).
    $like = $this->connection->escapeLike($module_name . '.');
    $config_names = $this->connection->select('config', 'c')
      ->fields('c', ['name'])
      ->condition('name', $like . '%', 'LIKE')
      ->execute()
      ->fetchAll();
    // Delete each config using configFactory.
    foreach ($config_names as $config_name) {
      $this->configFactory->getEditable($config_name->name)->delete();
    }

    // Remove from key_value table.
    $query = $this->connection->delete('key_value');
    $query->condition('collection', 'system.schema');
    $query->condition('name', [$module_name], 'IN');
    $query->execute();
  }

}
