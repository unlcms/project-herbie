<?php

namespace Drupal\unl_twig;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

/**
 * Extend Drupal's Twig_Extension class.
 */
class UnlTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'unl_twig';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
      // new TwigFunction({twig function name}, 'php function'),
    return [
      new TwigFunction('intersect', 'array_intersect'),
      new TwigFunction('intersect_key', 'array_intersect_key'),
      new TwigFunction('array_flip', 'array_flip'),
      new TwigFunction('parse_url', 'parse_url'),
      new TwigFunction('string_search', 'strpos'),
      new TwigFunction('string_lowercase', 'strtolower'),
      new TwigFunction('unique_id', 'uniqid'),
      new TwigFunction('preg_replace', 'preg_replace'),
    ];
  }

}
