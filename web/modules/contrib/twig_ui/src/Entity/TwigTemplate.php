<?php

namespace Drupal\twig_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Twig Template entity.
 *
 * @ConfigEntityType(
 *   id = "twig_template",
 *   label = @Translation("Twig template"),
 *   label_collection = @Translation("Twig templates"),
 *   label_singular = @Translation("Twig template"),
 *   label_plural = @Translation("Twig templates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Twig template",
 *     plural = "@count Twig templates",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\twig_ui\TwigTemplateListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\twig_ui\Entity\TwigTemplateForm",
 *       "add" = "Drupal\twig_ui\Entity\TwigTemplateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "clone" = "Drupal\twig_ui\Entity\TwigTemplateForm",
 *     }
 *   },
 *   admin_permission = "administer twig templates",
 *   config_prefix = "template",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "status",
 *     "id",
 *     "label",
 *     "theme_suggestion",
 *     "template_code",
 *     "themes",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/templates/{twig_template}/edit",
 *     "delete-form" = "/admin/structure/templates/{twig_template}/delete",
 *     "clone-form" = "/admin/structure/templates/{twig_template}/clone",
 *     "collection" = "/admin/structure/templates",
 *   }
 * )
 */
class TwigTemplate extends ConfigEntityBase {

  use StringTranslationTrait;

  /**
   * The Twig UI template ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The Twig UI template label.
   *
   * @var string
   */
  protected $label;

  /**
   * The theme suggestion.
   *
   * @var string
   */
  protected $theme_suggestion;

  /**
   * The template code.
   *
   * @var string
   */
  protected $template_code;

  /**
   * Themes for which the template is used.
   *
   * @var array
   */
  protected $themes;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $template_manager = \Drupal::service('twig_ui.template_manager');

    // If the template is enabled.
    if ($this->status()) {
      $template_manager->syncTemplateFiles($this);
      $this->flushCache();
      if (!$update) {
        \Drupal::service('messenger')->addMessage($this->t('The <em>%label</em> Twig template was created.', [
          '%label' => $this->label(),
        ]));
      }
      else {
        \Drupal::service('messenger')->addMessage($this->t('The <em>%label</em> Twig template was updated.', [
          '%label' => $this->label(),
        ]));
      }
    }
    // If the template is disabled.
    else {
      if (!$update) {
        // No need to call template manager. No files are being written.
        \Drupal::service('messenger')->addMessage($this->t('The <em>%label</em> Twig template was created but is disabled.', [
          '%label' => $this->label(),
        ]));
      }
      else {
        // Remove template files so they aren't registered.
        $template_manager->deleteTemplateFiles($this);
        $this->flushCache();
        \Drupal::service('messenger')->addMessage($this->t('The <em>%label</em> Twig template was disabled.', [
          '%label' => $this->label(),
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    $template_manager = \Drupal::service('twig_ui.template_manager');
    foreach ($entities as $entity) {
      $template_manager->deleteTemplateFiles($entity);
    }
    self::flushCache();
  }

  /**
   * Flushes certain caches and rebuilds kernel.
   *
   * Adapted from drupal_flush_all_caches().
   */
  private static function flushCache() {
    \Drupal::service('cache.bootstrap')->deleteAll();
    \Drupal::service('cache.default')->deleteAll();
    if (!empty(\Drupal::hasService('cache.dynamic_page_cache'))) {
      \Drupal::service('cache.dynamic_page_cache')->deleteAll();
    }
    if (!empty(\Drupal::hasService('cache.page'))) {
      \Drupal::service('cache.page')->deleteAll();
    }
    \Drupal::service('cache.render')->deleteAll();
    \Drupal::service('twig')->invalidate();

    // Rebuild module and theme data.
    $module_data = \Drupal::service('extension.list.module')
      ->reset()
      ->getList();

    // Rebuild and reboot a new kernel. A simple DrupalKernel reboot is not
    // sufficient, since the list of enabled modules might have been adjusted
    // above due to changed code.
    $files = [];
    $modules = [];
    foreach ($module_data as $name => $extension) {
      if ($extension->status) {
        $files[$name] = $extension;
        $modules[$name] = $extension->weight;
      }
    }
    $modules = module_config_sort($modules);
    \Drupal::service('kernel')
      ->updateModules($modules, $files);
  }

}
