<?php

namespace Drupal\config_inspector\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\config_inspector\ConfigInspectorManager;
use Drupal\Core\Config\StorageInterface;
use Drush\Commands\DrushCommands;

/**
 * Provides commands for config inspector.
 */
class InspectorCommands extends DrushCommands {

  /**
   * The configuration inspector manager.
   *
   * @var \Drupal\config_inspector\ConfigInspectorManager
   */
  protected $inspector;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * Constructs InspectorCommands object.
   *
   * @param \Drupal\config_inspector\ConfigInspectorManager $config_inspector_manager
   *   The configuration inspector manager.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The active configuration storage.
   */
  public function __construct(ConfigInspectorManager $config_inspector_manager, StorageInterface $storage) {
    parent::__construct();
    $this->inspector = $config_inspector_manager;
    $this->activeStorage = $storage;
  }

  /**
   * Inspect config for schema errors.
   *
   * @param string $key
   *   (Optional) Configuration key.
   * @param array $options
   *   (Optional) Options array.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   List of inspections.
   *
   * @option only-error
   *   Display only errors.
   * @option detail
   *   Show detailed errors.
   * @option skip-keys
   *   Configuration keys to skip.
   *
   * @usage drush config:inspect
   *   Inspect whole config for schema errors.
   * @usage drush config:inspect --detail
   *   Inspect whole config for schema errors but details errors.
   * @usage drush config:inspect --only-error --detail
   *   Inspect whole config for schema errors and display only errors if any.
   *
   * @field-labels
   *   key: Key
   *   status: Status
   * @default-fields key,status
   *
   * @command config:inspect
   * @aliases inspect_config
   */
  public function inspect($key = '', array $options = [
    'only-error' => FALSE,
    'detail' => FALSE,
    'skip-keys' => '',
  ]) {
    $rows = [];
    $exitCode = self::EXIT_SUCCESS;
    $keys = empty($key) ? $this->activeStorage->listAll() : [$key];
    $onlyError = $options['only-error'];
    $detail = $options['detail'];
    $skipKeys = array_fill_keys(explode(',', $options['skip-keys']), '1');

    foreach ($keys as $name) {
      if (isset($skipKeys[$name])) {
        continue;
      }
      if (!$this->inspector->hasSchema($name)) {
        $status = dt('No schema');
      }
      else {
        $result = $this->inspector->checkValues($name);
        if (is_array($result)) {
          $exitCode = self::EXIT_FAILURE;
          if ($detail) {
            foreach ($result as $key => $error) {
              $rows[$key] = ['key' => $key, 'status' => $error];
            }
            continue;
          }
          else {
            $status = dt('@count errors', ['@count' => count($result)]);
          }
        }
        else {
          if ($onlyError) {
            continue;
          }
          $status = dt('Correct');
        }
      }
      $rows[$name] = ['key' => $name, 'status' => $status];
    }

    return CommandResult::dataWithExitCode(new RowsOfFields($rows), $exitCode);
  }

}
