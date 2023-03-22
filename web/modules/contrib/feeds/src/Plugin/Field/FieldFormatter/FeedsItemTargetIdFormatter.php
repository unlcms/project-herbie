<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceIdFormatter;

/**
 * Plugin implementation of the 'feeds_item_target_id' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_item_target_id",
 *   label = @Translation("Feed ID"),
 *   description = @Translation("Display the ID of the feed entity."),
 *   field_types = {
 *     "feeds_item"
 *   }
 * )
 */
class FeedsItemTargetIdFormatter extends EntityReferenceIdFormatter {}
