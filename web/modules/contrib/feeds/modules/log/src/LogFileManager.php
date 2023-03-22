<?php

namespace Drupal\feeds_log;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Service for managing log files.
 */
class LogFileManager implements LogFileManagerInterface {

  /**
   * The feeds log configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The StreamWrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new LogFileManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->config = $config_factory->get('feeds_log.settings');
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedsLogDirectory(): string {
    $dir = $this->config->get('log_dir');
    if ($dir) {
      return $dir;
    }

    return $this->getDefaultFeedsLogDirectory();
  }

  /**
   * {@inheritdoc}
   */
  public function saveData($data, string $filename): string {
    $destination = $this->getFeedsLogDirectory() . '/' . $filename;
    $directory = $this->fileSystem->dirname($destination);
    $this->prepareDirectory($directory);

    $this->fileSystem->saveData($data, $destination);
    return $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function removeFiles(int $import_log_id) {
    $directory = $this->getFeedsLogDirectory() . '/' . $import_log_id;
    $this->fileSystem->deleteRecursive($directory);
  }

  /**
   * Returns the default log directory.
   *
   * @return string
   *   The default log directory.
   */
  protected function getDefaultFeedsLogDirectory(): string {
    $schemes = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::VISIBLE);
    $scheme = isset($schemes['private']) ? 'private' : 'public';
    return $scheme . '://feeds/log';
  }

  /**
   * Prepares the specified directory for writing files to it.
   *
   * The directory gets created in case it doesn't exist yet.
   *
   * @param string $dir
   *   The directory to prepare.
   *
   * @throws \RuntimeException
   *   In case the directory could not be created or made writable.
   */
  protected function prepareDirectory(string $dir) {
    if (!$this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new \RuntimeException(t('Feeds directory either cannot be created or is not writable.'));
    }
  }

}
