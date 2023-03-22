<?php

namespace Drupal\Tests\twig_ui\Traits;

/**
 * Helper methods for htaccess-related tests.
 */
trait HtaccessTestTrait {

  /**
   * Create the Twig UI templates directory.
   */
  public function createTemplatesDirectory() {
    $directory = $this->htaccessTestTraitTemplateManager()::DIRECTORY_PATH;
    return $this->htaccessTestTraitFileSystem()->mkdir($directory);
  }

  /**
   * Deletes the Twig UI templates directory.
   */
  public function deleteTemplatesDirectory() {
    $directory = $this->htaccessTestTraitTemplateManager()::DIRECTORY_PATH;
    $this->htaccessTestTraitFileSystem()->deleteRecursive($directory);
  }

  /**
   * Deletes the .htaccess file.
   */
  public function deleteHtaccessFile() {
    $directory = $this->htaccessTestTraitTemplateManager()::DIRECTORY_PATH;
    $this->htaccessTestTraitFileSystem()->delete($directory . '/.htaccess');
  }

  /**
   * Makes a directory or file unwritable.
   *
   * @param string $path
   *   Path to a directory or file.
   */
  public function makeUnwritable($path) {
    $this->htaccessTestTraitFileSystem()->chmod($path, 0000);
  }

  /**
   * Makes a directory or file writable.
   *
   * @param string $path
   *   Path to a directory or file.
   */
  public function makeWritable($path) {
    $this->htaccessTestTraitFileSystem()->chmod($path, 0775);
  }

  /**
   * Helper method to return template manager service.
   *
   * @return \Drupal\twig_ui\TemplateManager
   *   The template manager.
   */
  protected function htaccessTestTraitTemplateManager() {
    if (isset($this->templateManager)) {
      return $this->templateManager;
    }
    else {
      return \Drupal::service('twig_ui.template_manager');
    }
  }

  /**
   * Helper method to return file system service.
   *
   * @return \Drupal\twig_ui\TemplateManager
   *   The template manager.
   */
  protected function htaccessTestTraitFileSystem() {
    if (isset($this->fileSystem)) {
      return $this->fileSystem;
    }
    else {
      return \Drupal::service('file_system');
    }
  }

}
