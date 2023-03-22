<?php

namespace Drupal\feeds\Feeds\Processor;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\StateInterface;

/**
 * Defines a base processor plugin class.
 */
abstract class ProcessorBase extends PluginBase implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function postProcess(FeedInterface $feed, StateInterface $state) {
    $tokens = [
      '@feed' => $feed->label(),
      '@item' => $this->getItemLabel(),
      '@items' => $this->getItemLabelPlural(),
    ];

    if ($state->created) {
      $state->setMessage($this->formatPlural($state->created, '@feed: Created @count @item.', '@feed: Created @count @items.', $tokens));
    }
    if ($state->updated) {
      $state->setMessage($this->formatPlural($state->updated, '@feed: Updated @count @item.', '@feed: Updated @count @items.', $tokens));
    }
    if ($state->failed) {
      $state->setMessage($this->formatPlural($state->failed, '@feed: Failed importing @count @item.', '@feed: Failed importing @count @items.', $tokens), 'error');
    }
    if (!$state->created && !$state->updated && !$state->failed) {
      $state->setMessage($this->t('@feed: There are no new @items.', $tokens));
    }

    // Find out how many items were cleaned.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    if ($clean_state->cleaned) {
      $clean_state->setMessage($this->formatPlural($clean_state->cleaned, '@feed: Cleaned @count @item.', '@feed: Cleaned @count @items.', $tokens));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postClear(FeedInterface $feed, StateInterface $state) {
    $tokens = [
      '@item' => $this->getItemLabel(),
      '@items' => $this->getItemLabelPlural(),
      '%title' => $feed->label(),
    ];

    if ($state->deleted) {
      $state->setMessage($this->formatPlural($state->deleted, 'Deleted @count @item from %title.', 'Deleted @count @items from %title.', $tokens));
    }
    else {
      $state->setMessage($this->t('There are no @items to delete.', $tokens));
    }
  }

}
