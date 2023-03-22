<?php

namespace Drupal\feeds_log;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for Feeds import logs.
 */
class LogViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['feeds_import_log_entry']['table']['group'] = $this->t('Feeds log entry');
    $data['feeds_import_log_entry']['table']['base'] = [
      'field' => 'lid',
      'title' => t('Feeds log entries'),
      'help' => t('Contains a list of Feeds log entries.'),
    ];

    $data['feeds_import_log_entry']['table']['join'] = [
      'feeds_import_log' => [
        'field' => 'import_id',
        'left_field' => 'import_id',
      ],
    ];

    $data['feeds_import_log_entry']['lid'] = [
      'title' => $this->t('ID'),
      'help' => $this->t('The ID of log entry.'),
      'field' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['import_id'] = [
      'title' => t('Import ID'),
      'help' => t('The ID of the Feeds Import Log that this log entry belongs to.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'relationship' => [
        'title' => t('Feeds import log'),
        'help' => t('The Feeds Import Log that this log entry belongs to.'),
        'base' => 'feeds_import_log',
        'base field' => 'import_id',
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['feed_id'] = [
      'title' => t('Feed ID'),
      'help' => t('The ID of the feed that this log entry belongs to.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'relationship' => [
        'title' => t('Feed'),
        'help' => t('The feed that this log entry belongs to.'),
        'base' => 'feeds_feed',
        'base field' => 'fid',
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['entity_id'] = [
      'title' => t('Entity ID'),
      'help' => t('The ID of the entity that this log entry belongs to.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['entity_type_id'] = [
      'title' => t('Entity type ID'),
      'help' => t('The type of the entity that was involved.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['entity_label'] = [
      'title' => t('Entity label'),
      'help' => t('An alternative for identifying the entity, because the entity may not exist yet or it may have been deleted.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['item'] = [
      'title' => t('Item'),
      'help' => t('Uri of the logged item, if available.'),
      'field' => [
        'id' => 'feeds_log_uri',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['operation'] = [
      'title' => t('Operation'),
      'help' => t('The type of the operation, for example "created", "updated", or "cleaned".'),
      'field' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'feeds_log_operations',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['message'] = [
      'title' => t('Message'),
      'help' => t('The actual message of the log entry.'),
      'field' => [
        'id' => 'feeds_log_message',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['variables'] = [
      'title' => t('Variables'),
      'help' => t('The variables of the log entry in a serialized format.'),
      'field' => [
        'id' => 'serialized',
        'click sortable' => FALSE,
      ],
      'argument' => [
        'id' => 'string',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['feeds_import_log_entry']['timestamp'] = [
      'title' => t('Timestamp'),
      'help' => t('Date when the event occurred.'),
      'field' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'date',
      ],
    ];

    return $data;
  }

}
