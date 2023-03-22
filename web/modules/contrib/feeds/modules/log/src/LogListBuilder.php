<?php

namespace Drupal\feeds_log;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of feeds import log entities.
 *
 * @see \Drupal\feeds_log\Entity\ImportLog
 */
class LogListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new LogListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['view'] = [
      'title' => $this->t('View entries'),
      'weight' => 1,
      'url' => $entity->toUrl(),
    ];
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['feed'] = $this->t('Feed');
    $header['id'] = $this->t('ID');
    $header['start'] = $this->t('Import start time');
    $header['end'] = $this->t('Import finish time');
    $header['sources'] = $this->t('Sources');
    $header['entries_count'] = $this->t('Number of entries');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\feeds_log\ImportLogInterface $entity */
    $row['feed'] = $entity->getFeedLabel();
    $row['id'] = $entity->id();
    $start = $entity->getImportStartTime();
    $row['start'] = $start ? $this->dateFormatter->format($start, 'medium') : '';
    $end = $entity->getImportFinishTime();
    $row['end'] = $end ? $this->dateFormatter->format($end, 'medium') : '';
    $row['entries_count'] = $entity->getQuery()
      ->countQuery()
      ->execute()
      ->fetchField();

    $sources = $entity->getSources();
    if ($sources) {
      $sources_list = [
        '#theme' => 'item_list',
        '#items' => [],
      ];
      foreach ($sources as $delta => $source) {
        $sources_list['#items'][$delta] = [
          '#type' => 'link',
          '#url' => Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($source)),
          '#title' => $source,
          '#attributes' => [
            'target' => '_blank',
          ],
        ];
      }
      $row['sources'] = \Drupal::service('renderer')->render($sources_list);
    }
    else {
      $row['sources'] = '';
    }

    return $row + parent::buildRow($entity);
  }

}
