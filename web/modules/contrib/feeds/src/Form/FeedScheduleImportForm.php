<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for importing a feed.
 */
class FeedScheduleImportForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to schedule the import of the feed %feed?', ['%feed' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The import will run during cron.') . ' ' . parent::getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Schedule import');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#disabled'] = $this->entity->isLocked();
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->startCronImport();

    $args = [
      '@type'  => $this->entity->getType()->label(),
      '%title' => $this->entity->label(),
    ];
    $this->logger('feeds')->notice('@type: scheduled import for %title.', $args);
    $this->messenger()->addStatus($this->t('%title has been scheduled for import.', $args));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
