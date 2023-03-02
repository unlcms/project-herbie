<?php

namespace Drupal\media_embed_view_mode_restrictions\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\media\Form\EditorMediaDialog;

/**
 * Decorates the EditorMediaDialog filter plugin provided by core Media module.
 *
 * This can be removed when https://www.drupal.org/node/3109289 is fixed.
 */
final class EditorMediaDialogDecorator extends FormBase {

  /**
   * The decorated form instance.
   *
   * @var \Drupal\media\Form\EditorMediaDialog
   */
  protected $decorated;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a EditorMediaDialog object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\media\Form\EditorMediaDialog $decorated
   *   Provides a media embed dialog for text editors.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityDisplayRepositoryInterface $entity_display_repository, EditorMediaDialog $decorated) {
    $this->entityRepository = $entity_repository;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_display.repository'),
      EditorMediaDialog::create($container)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->decorated->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    return $this->decorated->buildForm($form, $form_state, $editor);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // When the `alt` attribute is set to two double quotes, transform it to the
    // empty string: two double quotes signify "empty alt attribute". See above.
    if (trim($form_state->getValue(['attributes', 'alt'], '')) === '""') {
      $form_state->setValue(['attributes', 'alt'], '""');
    }

    // The `alt` attribute is optional: if it isn't set, the default value
    // simply will not be overridden. It's important to set it to FALSE
    // instead of unsetting the value.  This way we explicitly inform
    // the client side about the new value.
    if ($form_state->hasValue(['attributes', 'alt']) && trim($form_state->getValue(['attributes', 'alt'])) === '') {
      $form_state->setValue(['attributes', 'alt'], FALSE);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-media-dialog-form', $form));
    }
    else {
      // Only send back the relevant values.
      $values = [
        'hasCaption' => $form_state->getValue('hasCaption'),
        'attributes' => $form_state->getValue('attributes'),
      ];
      $response->addCommand(new EditorDialogSave($values));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Gets the default value for the view mode form element.
   *
   * @param array $view_mode_options
   *   The array of options for the view mode form element.
   * @param \Drupal\filter\Plugin\FilterInterface $media_embed_filter
   *   The media embed filter.
   * @param string $media_element_view_mode_attribute
   *   The data-view-mode attribute on the <drupal-media> element.
   *
   * @return string|null
   *   The default value for the view mode form element.
   */
  public static function getViewModeDefaultValue(array $view_mode_options, FilterInterface $media_embed_filter, $media_element_view_mode_attribute) {
    return static::$decorated->getViewModeDefaultValue($view_mode_options, $media_embed_filter, $media_element_view_mode_attribute);
  }

  /**
   * Gets the name of an image media item's source field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item being embedded.
   *
   * @return string|null
   *   The name of the image source field configured for the media item, or
   *   NULL if the source field is not an image field.
   */
  protected function getMediaImageSourceFieldName(MediaInterface $media) {
    return $this->decorated->getMediaImageSourceFieldName($media);
  }

}
