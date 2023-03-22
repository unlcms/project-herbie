<?php

namespace Drupal\feeds;

/**
 * Describes operations that can happen on Feed items.
 */
class StateType {

  const CREATE = 'created';
  const UPDATE = 'updated';
  const DELETE = 'deleted';
  const SKIP = 'skipped';
  const FAIL = 'failed';
  const CLEAN = 'cleaned';

}
