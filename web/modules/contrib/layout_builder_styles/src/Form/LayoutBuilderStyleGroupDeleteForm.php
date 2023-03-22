<?php

namespace Drupal\layout_builder_styles\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Layout Builder Style Group entities.
 */
class LayoutBuilderStyleGroupDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prevent deleting a group that has styles associated with it.
    $numStylesInThisGroup = $this
      ->entityTypeManager
      ->getStorage('layout_builder_style')
      ->getQuery()
      ->condition('group', $this->entity->id())
      ->count()
      ->accessCheck()
      ->execute();
    if ($numStylesInThisGroup) {
      $caption = '<p>' . $this->t(
        'The %type group is associated with one or more styles. You may not remove this group until you have removed all styles associated with it, or reassigned those styles to a different group.',
        ['%type' => $this->entity->label()]
      ) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.layout_builder_style_group.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addMessage(
      $this->t('Deleted the %label style group.',
        [
          '@type' => $this->entity->bundle(),
          '%label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
