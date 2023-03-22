<?php

namespace Drupal\layout_builder_styles;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder_styles\Entity\LayoutBuilderStyleGroup;

/**
 * Provides a listing of layout builder style entities.
 */
class LayoutBuilderStyleListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_styles_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    $header['group'] = $this->t('Group');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /** @var \Drupal\layout_builder_styles\Entity\LayoutBuilderStyle $entity */
    $row['label'] = $entity->label();
    $row['id'] = ['#plain_text' => $entity->id()];
    $row['type'] = ['#plain_text' => $entity->getType()];
    $group = $entity->getGroup();
    if (empty($group) || empty(LayoutBuilderStyleGroup::load($group))) {
      $row['group'] = ['#plain_text' => ''];
    }
    else {
      // Show the label, not the group ID.
      $group = LayoutBuilderStyleGroup::load($group);
      $row['group'] = ['#plain_text' => $group->label()];
    }

    return $row + parent::buildRow($entity);
  }

}
