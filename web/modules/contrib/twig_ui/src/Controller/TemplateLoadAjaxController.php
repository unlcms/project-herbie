<?php

namespace Drupal\twig_ui\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DestructableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The template load AJAX controller.
 */
class TemplateLoadAjaxController implements ContainerInjectionInterface {

  /**
   * Twig UI's immutable registry.
   *
   * @var \Drupal\Core\DestructableInterface
   */
  protected $theme;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\DestructableInterface $immutable_registry
   *   Twig UI's immutable registry.
   */
  public function __construct(DestructableInterface $immutable_registry) {
    $this->registry = $immutable_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('twig_ui.immutable_registry')
    );
  }

  /**
   * Gets a list of available templates given a theme.
   *
   * @param string $theme
   *   The theme's machine name.
   *
   * @return array
   *   An array of template names.
   */
  public function templates($theme) {
    $this->registry->setTheme($theme);
    $registry_templates = $this->registry->get();
    $registry_templates = array_keys($registry_templates);
    sort($registry_templates);

    return new JsonResponse(
      $registry_templates,
      200
    );
  }

  /**
   * Gets a template's code from the file system given a theme/template.
   *
   * @param string $theme
   *   The theme's machine name.
   * @param string $template
   *   The template name.
   *
   * @return array
   *   An array containing
   *   code: The template code
   *   file_path: The complete path to the template file.
   */
  public function templateCode($theme, $template) {
    $this->registry->setTheme($theme);
    $registry_templates = $this->registry->get();
    $template_file_path = $registry_templates[$template]['path'] . '/' . $registry_templates[$template]['template'] . '.html.twig';

    $template_code = file_get_contents($template_file_path);

    return new JsonResponse(
      [
        'raw_code' => $template_code,
        'escaped_code' => htmlentities($template_code),
        'file_path' => $template_file_path,
      ],
      200
    );
  }

}
