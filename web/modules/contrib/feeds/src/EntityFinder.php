<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Searches for existing entities by a certain field.
 */
class EntityFinder implements EntityFinderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new EntityFinder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function findEntities(string $entity_type_id, string $field, $search, array $bundles = [], $multiple = FALSE) {
    // When referencing by UUID, use the EntityRepository service.
    if ($field === 'uuid') {
      if (NULL !== ($entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $search))) {
        return [$entity->id()];
      }
    }
    else {
      $query = $this->entityTypeManager->getStorage($entity_type_id)
        ->getQuery()
        ->accessCheck(FALSE);

      if (!empty($bundles)) {
        $query->condition($this->getBundleKey($entity_type_id), $bundles, 'IN');
      }

      $query->condition($field, $search);
      if (!$multiple) {
        $query->range(0, 1);
      }

      return array_filter($query->execute());
    }

    return [];
  }

  /**
   * Returns the entity type's bundle key.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The bundle key of the entity type.
   */
  protected function getBundleKey(string $entity_type_id) {
    return $this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle');
  }

}
