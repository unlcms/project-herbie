<?php

namespace Drupal\feeds;

/**
 * Interface for the Feeds entity finder service.
 */
interface EntityFinderInterface {

  /**
   * Searches for entities by entity key.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field
   *   The subfield to search in.
   * @param string|int $search
   *   The value to search for.
   * @param array $bundles
   *   (optional) The bundles to restrict the search by.
   * @param bool $multiple
   *   (optional) Whether or not to select multiple results.
   *   Defaults to FALSE.
   *
   * @return int[]
   *   A list of entity ID's that were found.
   */
  public function findEntities(string $entity_type_id, string $field, $search, array $bundles = [], $multiple = FALSE);

}
