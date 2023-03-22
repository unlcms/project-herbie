<?php

namespace Drupal\twig_ui;

use Drupal\twig_ui\Entity\TwigTemplate;

/**
 * The interface for TemplateManager.
 */
interface TemplateManagerInterface {

  /**
   * The base directory path for Twig UI template files.
   *
   * This constant must use a stream wrapper (e.g. private://).
   */
  const DIRECTORY_PATH = 'public://twig_ui';

  /**
   * Retrieves a listing of all Twig UI templates.
   *
   * @return array
   *   An array of TwigTemplate objects.
   */
  public function getTemplates();

  /**
   * Retrieves all Twig UI templates registered for a given theme.
   *
   * @param string $theme
   *   The machine name of a theme.
   *
   * @return mixed
   *   An array of TwigTemplate objects; NULL if none are found.
   */
  public function getTemplatesByTheme($theme);

  /**
   * Retrieves a Twig UI template by machine name.
   *
   * @param string $id
   *   The machine name of a Twig UI template.
   *
   * @return mixed
   *   A \Drupal\twig_ui\Entity\TwigTemplate object is a template is found.
   *   NULL if no template is found.
   */
  public function getTemplate($id);

  /**
   * Checks if an active Twig UI template exists for a given theme.
   *
   * @param string $suggestion
   *   A theme suggestion (e.g. template name with underscores).
   * @param string $theme
   *   The machine name of a theme.
   *
   * @return mixed
   *   Machine name of Twig UI template; FALSE if templates does not exist.
   */
  public function templateExists($suggestion, $theme);

  /**
   * Gets the active themes.
   *
   * @return array
   *   An array of Drupal\Core\Extension\Extension objects.
   */
  public function getActiveThemes();

  /**
   * Gets allowed themes.
   *
   * @return array
   *   An array of theme keys.
   */
  public function getAllowedThemes();

  /**
   * Syncs Twig UI template entities with template files in the file system.
   *
   * @param \Drupal\twig_ui\Entity\TwigTemplate $template
   *   A Twig UI template.
   */
  public function syncTemplateFiles(TwigTemplate $template);

  /**
   * Deletes all Twig UI template files.
   *
   * @param \Drupal\twig_ui\Entity\TwigTemplate $template
   *   A Twig UI template.
   */
  public function deleteTemplateFiles(TwigTemplate $template);

  /**
   * Writes a template file to the filesystem.
   *
   * @param \Drupal\twig_ui\Entity\TwigTemplate $template
   *   A Twig UI template.
   * @param string $theme
   *   The machine name of a theme.
   */
  public function writeTemplateFile(TwigTemplate $template, $theme);

  /**
   * Deletes a template file from the filesystem.
   *
   * @param \Drupal\twig_ui\Entity\TwigTemplate $template
   *   A Twig UI template.
   * @param string $theme
   *   The machine name of a theme.
   */
  public function deleteTemplateFile(TwigTemplate $template, $theme);

  /**
   * Returns the directory for a given theme.
   *
   * @param string $theme
   *   The machine name of a theme.
   * @param bool $stream_wrapper
   *   Whether or not the path should be returned in a stream wrapper.
   *
   * @return string
   *   The directory for a given theme.
   */
  public function getDirectoryPathByTheme($theme, $stream_wrapper = TRUE);

  /**
   * Generates the template file's path.
   *
   * @param Drupal\twig_ui\Entity\TwigTemplate $template
   *   A Twig UI template.
   * @param string $theme
   *   The machine name of a theme.
   *
   * @return string
   *   The template file's path, including the filename.
   */
  public function getTemplatePath(TwigTemplate $template, $theme);

  /**
   * Generates the template file's name.
   *
   * @param Drupal\twig_ui\Entity\TwigTemplate $template
   *   A Twig UI template.
   * @param bool $extension
   *   Whether or not the file extension (.html.twig) be included.
   *
   * @return string
   *   The template file name.
   */
  public function getTemplateFileName(TwigTemplate $template, $extension = TRUE);

  /**
   * Prepares the Twig UI templates directory.
   *
   * @return mixed
   *   TRUE if successful.
   *   A string if not successful.
   */
  public static function prepareTemplatesDirectory();

}
