<?php

namespace Drupal\unl_news\Form;

/**
 * The settings form for ianrnews.unl.edu integration within the UNL News module.
 */
class IANRSettingsForm extends SettingsForm {

  /**
   * Tag config name for this newsroom.
   *
   * @var string
   */
  const SETTINGS_TAGS_NAME = 'ianrnews_tag_ids';

  /**
   * Cache bin name.
   *
   * @var string
   */
  const CACHE_NAME = 'unl_news.ianrnews_tags';

  /**
   * URL with a JSON list of tags.
   *
   * @var string
   */
  const TAG_API_ENDPOINT = 'https://ianrnews.unl.edu/api/v1/tags?format=json';

  /**
   * Queue name.
   *
   * @var string
   */
  const QUEUE_NAME = 'ianrnews_queue_processor';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unl_news_ianrnews_settings';
  }

}
