<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for feeds feed items.
 */
class FeedViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);

    if ($entity->isLocked()) {
      $state = $entity->getState(StateInterface::PROCESS);

      $build['state'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('Current import state'),
        '#items' => [],
      ];

      $labels = [
        'created' => $this->t('Created'),
        'updated' => $this->t('Updated'),
        'deleted' => $this->t('Deleted'),
        'skipped' => $this->t('Skipped'),
        'failed' => $this->t('Failed'),
      ];
      foreach ($labels as $key => $label) {
        $build['state']['#items'][$key] = [
          '#markup' => $this->t('@label: @value', [
            '@label' => $label,
            '@value' => $state->{$key},
          ]),
        ];
      }

      // Display messages.
      $state->displayMessages();
    }

    return $build;
  }

}
