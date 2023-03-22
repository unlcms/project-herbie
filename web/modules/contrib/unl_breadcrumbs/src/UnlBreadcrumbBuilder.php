<?php

namespace Drupal\unl_breadcrumbs;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * A version of PathBasedBreadcrumbBuilder extended to meet UNL needs.
 */
class UnlBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The inbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The patch matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack that controls the lifecycle of requests.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A helper class to determine whether the route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Provides the default implementation of the active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrail
   */
  protected $menuActiveTrail;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * UNL Breadcrumbs config (unl_breadcrumbs.settings).
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Site config (system.site)
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $siteConfig;

  /**
   * Constructs the UnlBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack that controls the lifecycle of requests.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   A helper class to determine whether the route is an admin one.
   * @param \Drupal\Core\Menu\MenuActiveTrail $menu_active_trail
   *   Provides the default implementation of the active menu trail service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   */
  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path, PathMatcherInterface $path_matcher = NULL, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, AdminContext $admin_context, MenuActiveTrail $menu_active_trail, CurrentRouteMatch $route_match) {
    parent::__construct($context, $access_manager, $router, $path_processor, $config_factory, $title_resolver, $current_user, $current_path, $path_matcher);
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->adminContext = $admin_context;
    $this->menuActiveTrail = $menu_active_trail;
    $this->routeMatch = $route_match;
    $this->config = $config_factory->get('unl_breadcrumbs.settings');
    $this->siteConfig = $config_factory->get('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Disable for admin routes.
    if ($this->adminContext->isAdminRoute()) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Get breadcrumb array
    // from \Drupal\system\PathBasedBreadcrumbBuilder::build().
    $breadcrumbs = parent::build($route_match);

    // If initial config has not been set, then simply return the breadcrumbs
    // generated by \Drupal\system\PathBasedBreadcrumbBuilder::build().
    if ($this->config->isNew()) {
      return $breadcrumbs;
    }

    // Links can only be added to to breadcrumbs; Links cannot be modified or
    // removed. Thus, we copy the links and cache data to add to a new
    // breadcrumbs instance.
    $links = $breadcrumbs->getLinks();
    $cache_contexts = $breadcrumbs->getCacheContexts();
    $cache_tags = $breadcrumbs->getCacheTags();
    $cache_max_age = $breadcrumbs->getCacheMaxAge();

    // Add additional cache contexts for when dealing with groups,
    // domain access, or other cases with different addresses to same content.
    $cache_contexts[] = 'url.path';
    $cache_contexts[] = 'url.query_args';
    
    // Create new Breadcrumb instance.
    $breadcrumbs = new Breadcrumb();
    $breadcrumbs->addCacheContexts($cache_contexts);
    $breadcrumbs->addCacheTags($cache_tags);
    $breadcrumbs->mergeCacheMaxAge($cache_max_age);

    // It's easier to insert interstitial items if the links array is reversed.
    $links = array_reverse($links);
    // Remove "Home" link.
    array_pop($links);

    // Set site root breadcrumb.
    if ($this->config->get('site_root_breadcrumb_title_use_site_name')) {
      $links[] = Link::createFromRoute($this->siteConfig->get('name'), '<front>');
    }
    elseif ($site_root_breadcrumb_title = $this->config->get('site_root_breadcrumb_title')) {
      $links[] = Link::createFromRoute($site_root_breadcrumb_title, '<front>');
    }

    // Add interstitial links.
    $interstitial_items = $this->config->get('interstitial_breadcrumbs');
    if (!empty($interstitial_items)) {
      $interstitial_items = array_reverse($interstitial_items);
      foreach ($interstitial_items as $item) {
        $url = Url::fromUri($item['url']);
        $links[] = new Link($item['title'], $url);
      }
    }
    // Add expire cache context for config changes.
    $breadcrumbs->addCacheableDependency($this->config);

    // Set root item to the flagship site.
    $url = Url::fromUri('https://www.unl.edu');
    $links[] = new Link('Nebraska', $url);

    $links = array_reverse($links);

    // Add page title if user has access to page.
    $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
    // Merge the access result's cacheability metadata.
    $breadcrumbs->addCacheableDependency($access);
    if ($access->isAllowed()) {
      // Add title for current page as final, unlinked breadcrumb.
      // If menu link exists, use menu link title.
      $active_link = $this->menuActiveTrail->getActiveLink();

      if ($active_link instanceof MenuLinkContent) {
        $title = $active_link->getTitle();

        // Add cacheable dependency for menu item.
        // This was shamelessly "borrowed" from the menu_breadcrumb module.
        $uuid = $active_link->getDerivativeId();
        $entities = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $uuid]);
        if ($entity = reset($entities)) {
          $breadcrumbs->addCacheableDependency($entity);
        }
      }
      // If no menu link, then get title from title resolver.
      else {
        $request = $this->requestStack->getCurrentRequest();
        $title = $this->titleResolver->getTitle($request, $route_match->getRouteObject());
      }
      if (!empty($title)) {
        $links[] = Link::createFromRoute($title, '<none>');
      }
    }

    // If page belongs to an entity, then add it as a cacheable dependency.
    if ($entity = $this->getPageEntity()) {
      $breadcrumbs->addCacheableDependency($entity);
    }

    return $breadcrumbs->setLinks($links);
  }

  /**
   * Attempts to return entity object for the current route.
   *
   * Code is from https://www.drupal.org/forum/support/module-development-and-code-questions/2014-07-25/drupal-8-get-entity-object-given#comment-12752478
   *
   * @return mixed
   *   entity object if one exists; otherwise, NULL
   */
  protected function getPageEntity() {
    $page_entity = &drupal_static(__FUNCTION__, NULL);
    if (isset($page_entity)) {
      return $page_entity ?: NULL;
    }
    foreach ($this->routeMatch->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $page_entity = $param;
        break;
      }
    }
    if (!isset($page_entity)) {
      // Some routes don't properly define entity parameters.
      // Thus, try to load them by its raw Id, if given.
      $types = $this->entityTypeManager->getDefinitions();
      foreach ($this->routeMatch->getParameters()->keys() as $param_key) {
        if (!isset($types[$param_key])) {
          continue;
        }
        if ($param = $this->routeMatch->getParameter($param_key)) {
          if (is_string($param) || is_numeric($param)) {
            try {
              $page_entity = $this->entityTypeManager->getStorage($param_key)->load($param);
            }
            catch (\Exception $e) {
            }
          }
          break;
        }
      }
    }
    if (!isset($page_entity) || !$page_entity->access('view')) {
      $page_entity = FALSE;
      return NULL;
    }
    return $page_entity;
  }

}
