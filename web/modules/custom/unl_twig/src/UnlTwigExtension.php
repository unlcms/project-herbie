<?php

namespace Drupal\unl_twig;

/**
 * Extend Drupal's Twig_Extension class.
 */
class UnlTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'unl_twig';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('intersect', 'array_intersect'),
    ];
  }

}
