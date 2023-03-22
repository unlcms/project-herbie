<?php

namespace Drupal\feeds_log\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form before clearing out the logs.
 */
class ClearLogConfirmForm extends FeedsLogConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the logs for feed %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute($this->returnRoute, [
      'feeds_feed' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete all logs for feed.
    $storage = $this->entityTypeManager->getStorage('feeds_import_log');
    $result = $storage->getQuery()
      ->condition('feed', $this->entity->id())
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($result)) {
      $entities = $storage->loadMultiple($result);
      $storage->delete($entities);
    }

    $this->messenger()->addStatus($this->t('Logs cleared for feed %label.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
