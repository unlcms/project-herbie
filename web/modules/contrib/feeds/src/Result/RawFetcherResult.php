<?php

namespace Drupal\feeds\Result;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileSystemInterface;

/**
 * A fetcher result object that accepts a raw string.
 *
 * This will write the string to a file on-demand if the parser requests it.
 */
class RawFetcherResult extends FetcherResult {

  use DependencySerializationTrait;

  /**
   * The raw input string.
   *
   * @var string
   */
  protected $raw;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a RawFetcherResult object.
   *
   * @param string $raw
   *   The raw result string.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   (optional) The file system service.
   */
  public function __construct($raw, FileSystemInterface $file_system = NULL) {
    $this->raw = $raw;
    if (is_null($file_system)) {
      $file_system = \Drupal::service('file_system');
    }
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return $this->sanitizeRaw($this->raw);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilePath() {
    // Write to a temporary file if the parser expects a file.
    if ($this->filePath) {
      return $this->filePath;
    }

    $this->filePath = $this->fileSystem->tempnam('temporary://', 'feeds-raw');
    file_put_contents($this->filePath, $this->getRaw());
    return $this->filePath;
  }

}
