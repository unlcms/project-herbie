<?php

namespace Drupal\feeds;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of feed types.
 *
 * @todo Would making this sortable help in specifying the importance of a feed?
 */
class FeedTypeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->getDescription();
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['label'] = $this->t('Label');
    $row['id'] = $this->t('Machine name');
    $row['description'] = $this->t('Description');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if ($entity->access('mapping') && $entity->hasLinkTemplate('mapping')) {
      $operations['mapping'] = [
        'title' => $this->t('Mapping'),
        'url' => $entity->toUrl('mapping'),
        // Appear after operation "edit".
        'weight' => 11,
      ];
    }

    uasort($operations, [SortArray::class, 'sortByWeightElement']);

    return $operations;
  }

}
