<?php

namespace Drupal\feeds_test_plugin\Feeds\Processor;

use Drupal\feeds\Feeds\Processor\EntityProcessorBase;

/**
 * Defines an entity_test processor.
 *
 * @FeedsProcessor(
 *   id = "entity:entity_test",
 *   title = @Translation("Test entity overridden"),
 *   description = @Translation("Creates test entities from feed items."),
 *   entity_type = "entity_test",
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class EntityTestProcessor extends EntityProcessorBase {}
