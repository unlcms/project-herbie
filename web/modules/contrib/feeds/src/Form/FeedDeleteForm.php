<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a Feed.
 */
class FeedDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the feed %feed?', ['%feed' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $item_count = $this->entity->getItemCount();
    if (!$item_count) {
      $message = $this->t('This feed has no imported items.');
    }
    else {
      $message = $this->formatPlural($item_count, 'This feed has 1 imported item that will remain on the site.', 'This feed has @count imported items that will remain on the site.');
    }
    return $message . ' ' . parent::getDescription();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Set the correct route once views can override paths.
   */
  public function getCancelUrl() {
    return $this->entity->toUrl();
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

    try {
      $args = [
        '@type'  => $this->entity->getType()->label(),
        '%title' => $this->entity->label(),
      ];
      $this->logger('feeds')->notice('@type: deleted %title.', $args);
    }
    catch (EntityStorageException $e) {
      // There was an error loading the feed type. Log a different message
      // instead.
      $args = [
        '@type'  => $this->entity->bundle(),
        '%title' => $this->entity->label(),
      ];
      $this->logger('feeds')->notice('Deleted %title of unknown feed type @type.', $args);
    }
    $this->messenger()->addMessage($this->t('%title has been deleted.', $args));

    $form_state->setRedirect('feeds.admin');
  }

}
