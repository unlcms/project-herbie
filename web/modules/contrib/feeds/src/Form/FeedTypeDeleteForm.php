<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for feed type deletion.
 */
class FeedTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_feeds = $this->entityTypeManager->getStorage('feeds_feed')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_feeds) {
      $caption = '<p>' . $this->formatPlural($num_feeds, '%type is used by 1 feed on your site. You can not remove this feed type until you have removed all of the %type feeds. <a href=":link">Return to feed types list.</a>', '%type is used by @count feeds on your site. You may not remove %type until you have removed all of the %type feeds. <a href=":link">Return to feed types list.</a>', [
        '%type' => $this->entity->label(),
        ':link' => '/admin/structure/feeds',
      ]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
