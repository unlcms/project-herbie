<?php

namespace Drupal\feeds;

/**
 * Definition for a missing target.
 */
class MissingTargetDefinition implements TargetDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return t('Error: target is missing');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyLabel($property) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDescription($property) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnique($property) {
    return FALSE;
  }

}
