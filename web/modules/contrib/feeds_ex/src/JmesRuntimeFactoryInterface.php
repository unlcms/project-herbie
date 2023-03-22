<?php

namespace Drupal\feeds_ex;

/**
 * Defines a factory interface for generating JMESPath runtime objects.
 */
interface JmesRuntimeFactoryInterface {

  /**
   * Represents \JmesPath\AstRuntime.
   *
   * @var string
   */
  const AST = 'ast';

  /**
   * Represents \JmesPath\CompilerRuntime.
   *
   * @var string
   */
  const COMPILER = 'compiler';

  /**
   * Creates a runtime object.
   *
   * @param string $type
   *   (optional) The type of Runtime to create.
   */
  public function createRuntime($type = NULL);

}
