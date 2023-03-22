<?php

namespace Drupal\feeds_log;

/**
 * Interface for the feeds log manager.
 */
interface LogFileManagerInterface {

  /**
   * Returns the uri of the configured log directory.
   *
   * @return string
   *   The configured log directory. This can be for example
   *   "private://feeds/log".
   */
  public function getFeedsLogDirectory(): string;

  /**
   * Saves data to the specified file relative to the feeds log directory.
   *
   * @param mixed $data
   *   The data to save to a file.
   * @param string $filename
   *   The path to the file to save the data to, relative to the feeds log
   *   directory.
   *
   * @return string
   *   The file uri the data was saved to. This includes the uri to the feeds
   *   log directory.
   */
  public function saveData($data, string $filename): string;

  /**
   * Cleans up log files from the specified import.
   *
   * @param int $import_log_id
   *   The import ID to clean up log files for.
   */
  public function removeFiles(int $import_log_id);

}
