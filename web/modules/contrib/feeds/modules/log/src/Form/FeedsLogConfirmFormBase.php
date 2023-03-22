<?php

namespace Drupal\feeds_log\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the logs.
 */
abstract class FeedsLogConfirmFormBase extends EntityConfirmFormBase {

  /**
   * The route to return to after submit or cancel.
   *
   * @var string
   */
  protected $returnRoute;

  /**
   * Constructs a new MappingForm object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    if (count($route_provider->getRoutesByNames(['view.feeds_import_logs.page_1']))) {
      $this->returnRoute = 'view.feeds_import_logs.page_1';
    }
    else {
      $this->returnRoute = 'feeds.log';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Overridden because values on the feed should not be changed.
  }

}
