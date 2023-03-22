<?php

namespace Drupal\feeds_log\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a feed log entity.
 */
class DeleteForm extends FeedsLogConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete log %id?', [
      '%id' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute($this->returnRoute, [
      'feeds_feed' => $this->entity->feed->target_id,
    ]);
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

    $this->messenger()->addMessage($this->t('Log %id has been deleted.', [
      '%id' => $this->entity->id(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
