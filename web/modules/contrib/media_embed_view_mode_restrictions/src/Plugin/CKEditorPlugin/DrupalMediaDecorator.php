<?php

namespace Drupal\media_embed_view_mode_restrictions\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalMedia;

/**
 * Decorates the core drupalmedia plugin.
 *
 * This can be removed when https://www.drupal.org/node/3109289 is fixed.
 */
final class DrupalMediaDecorator extends PluginBase implements ContainerFactoryPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface {


  /**
   * The decorated DrupalMedia CKEditor plugin.
   *
   * @var \Drupal\media\Plugin\CKEditorPlugin\DrupalMedia
   */
  protected $decorated;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new DrupalMedia plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\media\Plugin\CKEditorPlugin\DrupalMedia $decorated
   *   The decorated DrupalMedia CKEditor plugin.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DrupalMedia $decorated, ModuleExtensionList $extension_list_module) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->decorated = $decorated;
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      DrupalMedia::create($container, $configuration, $plugin_id, $plugin_definition),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return $this->decorated->isInternal();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return $this->decorated->getDependencies($editor);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return $this->decorated->getLibraries($editor);
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleExtensionList->getPath('media_embed_view_mode_restrictions') . '/js/plugins/drupalmedia/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return $this->decorated->getConfig($editor);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return $this->decorated->isEnabled($editor);
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return $this->decorated->getCssFiles($editor);
  }

}
