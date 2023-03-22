<?php

namespace Drupal\twig_ui;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * The TemplateManager class.
 */
class TemplateManager implements TemplateManagerInterface {

  /**
   * The entityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * An interface for helpers that operate on files and stream wrappers.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a TemplateManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   An interface for helpers that operate on files and stream wrappers.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ThemeHandlerInterface $theme_handler, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->themeHandler = $theme_handler;
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplates() {
    $templates = $this->entityTypeManager
      ->getStorage('twig_template')
      ->loadMultiple();

    return $templates;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplatesByTheme($theme) {
    $query = $this->entityTypeManager
      ->getStorage('twig_template')
      ->getQuery('AND');
    // See
    // https://www.drupal.org/project/drupal/issues/2248567#comment-13080439.
    $query->condition('themes.*', $theme);
    $ids = $query->execute();

    $templates = $this->entityTypeManager
      ->getStorage('twig_template')
      ->loadMultiple($ids);

    return (!empty($templates)) ? $templates : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate($id) {
    $templates = $this->entityTypeManager
      ->getStorage('twig_template')
      ->loadByProperties(['id' => $id]);

    return (is_array($templates)) ? array_shift($templates) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function templateExists($suggestion, $theme) {
    $query = $this->entityTypeManager
      ->getStorage('twig_template')
      ->getQuery('AND');
    $query->condition('theme_suggestion', $suggestion);
    $query->condition('status', TRUE);
    // See
    // https://www.drupal.org/project/drupal/issues/2248567#comment-13080439.
    $query->condition('themes.*', $theme);
    $templates = $query->execute();

    return (!empty($templates)) ? array_shift($templates) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemes() {
    return $this->themeHandler->listInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedThemes() {
    $config = $this->configFactory->get('twig_ui.settings');
    $allowed_themes = $config->get('allowed_themes');
    if ($allowed_themes == 'all') {
      $themes = $this->getActiveThemes();
      return array_keys($themes);
    }
    elseif ($allowed_themes == 'selected') {
      return $config->get('allowed_theme_list');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncTemplateFiles(TwigTemplate $template) {
    // Process template files for existing Twig UI template.
    if (isset($template->original)) {
      $original = $template->original;
      $original_status = $original->get('status');
      $status = $template->get('status');
      $original_theme_suggestion = $original->get('theme_suggestion');
      $theme_suggestion = $template->get('theme_suggestion');
      $original_code = $original->get('template_code');
      $code = $template->get('template_code');
      $original_themes = $original->get('themes');
      $themes = $template->get('themes');

      // If the theme_suggestion has changed or if the template code has
      // changed or if the status has changed, then delete all files for this
      // Twig UI template and write them again.
      if ($original_theme_suggestion != $theme_suggestion
        || $original_code != $code
        || $original_status != $status
        ) {
        $this->deleteTemplateFiles($original);

        foreach ($template->get('themes') as $theme) {
          $this->writeTemplateFile($template, $theme);
        }
      }
      // Otherwise, check if the designated themes has changed for this
      // Twig UI template.
      elseif ($original_themes != $themes) {
        // Remove template files.
        $remove_theme_templates = array_diff($original_themes, $themes);
        foreach ($remove_theme_templates as $theme) {
          $this->deleteTemplateFile($template, $theme);
        }

        // Add template files.
        $add_theme_templates = array_diff($themes, $original_themes);
        foreach ($add_theme_templates as $theme) {
          $this->writeTemplateFile($template, $theme);
        }
      }
    }
    // Process template files for new Twig UI template.
    else {
      foreach ($template->get('themes') as $theme) {
        $this->writeTemplateFile($template, $theme);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTemplateFiles(TwigTemplate $template) {
    foreach ($template->get('themes') as $theme) {
      $this->deleteTemplateFile($template, $theme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function writeTemplateFile(TwigTemplate $template, $theme) {
    $template_code = $template->get('template_code');
    $dir_path = self::getDirectoryPathByTheme($theme);
    $this->fileSystem->prepareDirectory($dir_path, FileSystem::CREATE_DIRECTORY);
    $this->fileSystem->saveData($template_code, $this->getTemplatePath($template, $theme), FileSystem::EXISTS_REPLACE);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTemplateFile(TwigTemplate $template, $theme) {
    $this->fileSystem->delete($this->getTemplatePath($template, $theme));
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPathByTheme($theme, $stream_wrapper = TRUE) {
    $path = self::DIRECTORY_PATH . '/' . $theme;

    // Returns path with stream wrapper (e.g. wrapper://twig_ui/my_theme).
    if ($stream_wrapper) {
      return $path;
    }
    // Returns path as a relative path from the docroot
    // (e.g. sites/default/files/twig_ui/my_theme).
    else {
      $stream_wrapper = $this->streamWrapperManager->getViaUri($path);
      list($scheme) = explode('://', $path, 2);
      return str_replace($scheme . '://', $stream_wrapper->getDirectoryPath() . '/', $path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplatePath(TwigTemplate $template, $theme) {
    return self::getDirectoryPathByTheme($theme) . '/' . $this->getTemplateFileName($template);
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateFileName(TwigTemplate $template, $extension = TRUE) {
    $extension_string = ($extension) ? '.html.twig' : '';
    return str_replace('_', '-', $template->get('theme_suggestion') . $extension_string);
  }

  /**
   * {@inheritdoc}
   */
  public static function prepareTemplatesDirectory() {
    $file_system = \Drupal::service('file_system');

    // Attempt to prepare the templates directory.
    $directory_path = self::DIRECTORY_PATH;
    if (!$file_system->prepareDirectory($directory_path, FileSystem::CREATE_DIRECTORY)) {
      return 'Unable to create templates directory';
    }

    // Write .htaccess file to the templates directory.
    // Delete existing .htaccess file first, if one exists.
    $htaccess_path = $directory_path . '/.htaccess';
    if (file_exists($htaccess_path)) {
      $file_system->delete($htaccess_path);
    }
    if (!\Drupal::service('file.htaccess_writer')->write($directory_path)) {
      return 'Unable to create .htaccess file in templates directory';
    }

    return TRUE;
  }

}
