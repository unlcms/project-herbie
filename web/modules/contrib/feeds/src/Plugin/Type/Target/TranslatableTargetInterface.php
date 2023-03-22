<?php

namespace Drupal\feeds\Plugin\Type\Target;

/**
 * Interface for translatable target plugins.
 */
interface TranslatableTargetInterface {

  /**
   * Checks if the language selected on the target exists.
   *
   * @return bool
   *   True, if the target configured language exists. False otherwise.
   */
  public function languageExists();

  /**
   * Checks if the target is translatable.
   *
   * Target is translatable when a language is set in configuration.
   *
   * @return bool
   *   True if the target translatable. False otherwise.
   */
  public function isTargetTranslatable();

  /**
   * Gets the configured language.
   *
   * @return string
   *   The configured language.
   */
  public function getLangcode();

}
