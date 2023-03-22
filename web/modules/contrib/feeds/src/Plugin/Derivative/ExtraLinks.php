<?php

namespace Drupal\feeds\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds extra menu links to the feed type menu.
 */
class ExtraLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Default maximum number of feed types to generate links for.
   *
   * @var int
   */
  const MAX_BUNDLE_NUMBER_DEFAULT = 10;

  /**
   * The storage handler for the config entity type 'feeds_feed_type'.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $feedTypeStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Maximum number of feed types to generate links for.
   *
   * @var int
   */
  protected $maxBundleNumber;

  /**
   * Constructs a new ExtraLinks object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $feed_type_storage
   *   The storage handler for the config entity type 'feeds_feed_type'.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct($base_plugin_id, ConfigEntityStorageInterface $feed_type_storage, ModuleHandlerInterface $module_handler, RouteProviderInterface $route_provider, ConfigFactoryInterface $config_factory) {
    $this->feedTypeStorage = $feed_type_storage;
    $this->moduleHandler = $module_handler;
    $this->routeProvider = $route_provider;
    $this->maxBundleNumber = $config_factory->get('admin_toolbar_tools.settings')
      ->get('max_bundle_number');
    if (is_null($this->maxBundleNumber)) {
      $this->maxBundleNumber = static::MAX_BUNDLE_NUMBER_DEFAULT;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')->getStorage('feeds_feed_type'),
      $container->get('module_handler'),
      $container->get('router.route_provider'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!$this->moduleHandler->moduleExists('admin_toolbar_tools')) {
      // The module 'Admin Toolbar Extra Tools' must be installed in order to
      // generate extra links.
      return [];
    }

    $links = [];

    // Limit number of feed types to generate links for.
    $bundle_ids = $this->feedTypeStorage->getQuery()
      ->accessCheck(FALSE)
      ->pager($this->maxBundleNumber)
      ->execute();

    foreach ($bundle_ids as $bundle_id) {
      foreach ($this->getRoutes() as $route_name => $details) {
        if ($this->routeExists($route_name)) {
          $links[$route_name . '.' . $bundle_id] = $details + [
            'route_name' => $route_name,
            'parent' => 'admin_toolbar_tools.extra_links:entity.feeds_feed_type.edit_form.' . $bundle_id,
            'route_parameters' => ['feeds_feed_type' => $bundle_id],
          ] + $base_plugin_definition;
        }
      }
    }

    return $links;
  }

  /**
   * Returns the routes and labels to generate links for.
   *
   * @return array
   *   The routes to generate links for in a pair of route name => details.
   */
  protected function getRoutes(): array {
    return [
      'entity.feeds_feed_type.mapping' => [
        'title' => $this->t('Mapping'),
        'weight' => -10,
      ],
      'entity.feeds_feed_type.sources' => [
        'title' => $this->t('Custom sources'),
        'weight' => -8,
      ],
    ];
  }

  /**
   * Determine if a route exists by name.
   *
   * @param string $route_name
   *   The name of the route to check.
   *
   * @return bool
   *   Whether a route with that route name exists.
   */
  public function routeExists(string $route_name): bool {
    return (count($this->routeProvider->getRoutesByNames([$route_name])) === 1);
  }

}
