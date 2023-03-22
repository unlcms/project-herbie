<?php

namespace Drupal\Tests\feeds_ex\Traits;

/**
 * Provides methods useful for Kernel and Functional tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsExCommonTrait {

  /**
   * Returns the absolute path to the Drupal root.
   *
   * @return string
   *   The absolute path to the directory where Drupal is installed.
   */
  protected function absolute() {
    return realpath(getcwd());
  }

  /**
   * Returns the absolute directory path of the Feed Extensible parsers module.
   *
   * @return string
   *   The absolute path to the Feeds module.
   */
  protected function absolutePath() {
    return $this->absolute() . '/' . \Drupal::service('extension.list.module')->getPath('feeds_ex');
  }

  /**
   * Returns the url to the Feeds Extensible parsers resources directory.
   *
   * @return string
   *   The url to the Feeds resources directory.
   */
  protected function resourcesUrl() {
    return \Drupal::request()->getSchemeAndHttpHost() . '/' . \Drupal::service('extension.list.module')->getPath('feeds_ex') . '/tests/resources';
  }

  /**
   * Returns the absolute directory path of the resources folder.
   *
   * @return string
   *   The absolute path to the resources folder.
   */
  protected function resourcesPath() {
    return $this->absolutePath() . '/tests/resources';
  }

}
