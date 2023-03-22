<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'feeds_item_target_entity_view' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_item_target_entity_view",
 *   label = @Translation("Rendered feed"),
 *   description = @Translation("Display the feed entity rendered by entity_view()."),
 *   field_types = {
 *     "feeds_item"
 *   }
 * )
 */
class FeedsItemTargetEntityFormatter extends EntityReferenceEntityFormatter {}
