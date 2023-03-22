<?php

namespace Drupal\externalauth\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete an authmap entry.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("authmap_link_delete")
 */
class AuthmapDeleteLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This is overridden to not call $this->getEntityTranslationRenderer()
    // which will break because we don't have an entity type. (And we assume
    // we can skip calling it because we never need to add extra tables/fields
    // in order to translate this link. As an aside: this class would be much
    // smaller if LinkBase didn't contain entity related code and if all non
    // entity related code was actually in LinkBase so we didn't need to copy
    // it from EntityLinkBase.)
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row): string {
    // From EntityLink:
    if ($this->options['output_url_as_text']) {
      return $this->getUrlInfo($row)->toString();
    }
    // From LinkBase, minus addLangCode() which needs an entity.
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = $this->getUrlInfo($row);
    $text = !empty($this->options['text']) ? $this->sanitizeValue($this->options['text']) : $this->getDefaultLabel();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row): Url {
    return Url::fromRoute('externalauth.authmap_delete_form', [
      'provider' => $row->authmap_provider,
      'uid' => $row->authmap_uid,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel(): TranslatableMarkup {
    return $this->t('delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    // Copy from EntityLinkBase. Maybe unnecessary, but harmless.
    $options = parent::defineOptions();
    $options['output_url_as_text'] = ['default' => FALSE];
    $options['absolute'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Copy from EntityLinkBase. Maybe unnecessary, but harmless.
    $form['output_url_as_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output the URL as text'),
      '#default_value' => $this->options['output_url_as_text'],
    ];
    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute link (begins with "http://")'),
      '#default_value' => $this->options['absolute'],
      '#description' => $this->t('Enable this option to output an absolute link. Required if you want to use the path as a link destination.'),
    ];
    parent::buildOptionsForm($form, $form_state);
    // Only show the 'text' field if we don't want to output the raw URL.
    $form['text']['#states']['visible'][':input[name="options[output_url_as_text]"]'] = ['checked' => FALSE];
  }

}
