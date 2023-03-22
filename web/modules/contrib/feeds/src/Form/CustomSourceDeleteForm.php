<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form for deleting a custom source.
 */
class CustomSourceDeleteForm extends ConfirmFormBase {

  /**
   * The feed type for which to delete a custom source.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The data of the custom source to delete.
   *
   * Consists of at least the following keys:
   * - label: the human readable name of the source.
   * - value: used by the parser, usually to query source data.
   * - machine_name: the machine name of the source.
   *
   * @var array
   */
  protected $source;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_custom_source_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the source %label from the feed type %feed_type?', [
      '%label' => $this->source['label'],
      '%feed_type' => $this->feedType->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.feeds_feed_type.sources', [
      'feeds_feed_type' => $this->feedType->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed type that we are deleting a custom source from.
   * @param string $key
   *   The custom source's ID.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FeedTypeInterface $feeds_feed_type = NULL, $key = NULL) {
    $this->feedType = $feeds_feed_type;
    if (!$this->feedType->customSourceExists($key)) {
      throw new NotFoundHttpException();
    }
    $this->source = $feeds_feed_type->getCustomSource($key);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->feedType->removeCustomSource($this->source['machine_name']);
    $this->feedType->save();

    $this->messenger()->addStatus($this->t('The source %label has been deleted from the feed type %feed_type.', [
      '%label' => $this->source['label'],
      '%feed_type' => $this->feedType->label(),
    ]));
    $form_state->setRedirect('entity.feeds_feed_type.sources', [
      'feeds_feed_type' => $this->feedType->id(),
    ]);
  }

}
