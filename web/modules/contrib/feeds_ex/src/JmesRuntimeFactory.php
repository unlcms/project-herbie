<?php

namespace Drupal\feeds_ex;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use JmesPath\AstRuntime;
use JmesPath\CompilerRuntime;
use RuntimeException;

/**
 * Defines a factory for generating JMESPath runtime objects.
 */
class JmesRuntimeFactory implements JmesRuntimeFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function createRuntime($type = NULL) {
    switch ($type) {
      case static::AST:
        return $this->createAstRuntime();

      case static::COMPILER:
      default:
        try {
          return $this->createCompilerRuntime(\Drupal::service('file_system')->realpath($this->getCompileDirectory()));
        }
        catch (RuntimeException $e) {
          // Fallback to AstRuntime if creating a CompilerRuntime failed.
          return $this->createRuntime(static::AST);
        }
    }
  }

  /**
   * Creates a runtime object of type \JmesPath\AstRuntime.
   */
  public function createAstRuntime() {
    return new AstRuntime();
  }

  /**
   * Creates a runtime object of type \JmesPath\CompilerRuntime.
   *
   * @param string $directory
   *   The compile directory.
   */
  public function createCompilerRuntime($directory) {
    return new CompilerRuntime($directory);
  }

  /**
   * Returns the compilation directory.
   *
   * @return string
   *   The directory JmesPath uses to store generated code.
   */
  protected function getCompileDirectory() {
    // Look for a previous directory.
    $directory = \Drupal::state()->get('feeds_ex_jmespath_compile_dir');

    // The temp directory doesn't exist, or has moved.
    if (!$this->validateCompileDirectory($directory)) {
      $directory = $this->generateCompileDirectory();
      \Drupal::state()->set('feeds_ex_jmespath_compile_dir', $directory);

      // Creates the directory with the correct perms. We don't check the
      // return value since if it didn't work, there's nothing we can do. We
      // just fallback to the AstRuntime anyway.
      $this->validateCompileDirectory($directory);
    }

    return $directory;
  }

  /**
   * Generates a directory path to store auto-generated PHP files.
   *
   * @return string
   *   A temp directory path.
   */
  protected function generateCompileDirectory() {
    $prefix = Crypt::randomBytesBase64(40);
    return 'temporary://' . $prefix . '_feeds_ex_jmespath_dir';
  }

  /**
   * Validates that a compile directory exists and is valid.
   *
   * @param string $directory
   *   A directory path.
   *
   * @return bool
   *   True if the directory exists and is writable, false if not.
   */
  protected function validateCompileDirectory($directory) {
    if (!$directory) {
      return FALSE;
    }

    return \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
  }

}
