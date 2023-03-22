<?php

namespace Drupal\feeds_test_entity\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * An entity test class without link templates.
 *
 * @ContentEntityType(
 *   id = "feeds_test_entity_test_no_links",
 *   label = @Translation("Test entity without links"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *   },
 *   base_table = "feeds_test_entity_test_no_links",
 *   admin_permission = "administer entity_test content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 * )
 */
class EntityTestNoLinks extends EntityTest {}
