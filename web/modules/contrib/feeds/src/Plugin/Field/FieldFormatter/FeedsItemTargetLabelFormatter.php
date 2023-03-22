<?php

namespace Drupal\feeds\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'feeds_item_target_label' formatter.
 *
 * @FieldFormatter(
 *   id = "feeds_item_target_label",
 *   label = @Translation("Feed label"),
 *   description = @Translation("Display the label of the feed entity."),
 *   field_types = {
 *     "feeds_item"
 *   }
 * )
 */
class FeedsItemTargetLabelFormatter extends EntityReferenceLabelFormatter {}
