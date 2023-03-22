<?php

namespace Drupal\layout_builder_styles;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of layout builder style entities.
 */
class LayoutBuilderStyleGroupListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_styles_group_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['multiselect'] = $this->t('Multiselect');
    $header['form_type'] = $this->t('Form type');
    $header['required'] = $this->t('Required');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /** @var \Drupal\layout_builder_styles\Entity\LayoutBuilderStyleGroup $entity */
    $row['label'] = $entity->label();
    $row['id'] = ['#plain_text' => $entity->id()];
    $row['multiselect'] = [
      '#plain_text' => $entity->getMultiselect() === LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE ? 'Yes' : 'No',
    ];
    $row['form_type'] = [
      '#plain_text' => $entity->getMultiselect() === LayoutBuilderStyleGroupInterface::TYPE_MULTIPLE ? $entity->getFormType() : 'select',
    ];
    $row['required'] = (empty($entity->getRequired())) ? ['#plain_text' => 'Optional'] : ['#plain_text' => 'Required'];
    return $row + parent::buildRow($entity);
  }

}
