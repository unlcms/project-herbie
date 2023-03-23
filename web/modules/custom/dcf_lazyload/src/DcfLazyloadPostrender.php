<?php

namespace Drupal\dcf_lazyload;

use Drupal\Component\Utility\Random;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides a class that implements TrustedCallbackInterface.
 *
 * Needed due to https://www.drupal.org/node/2966725.
 */
class DcfLazyloadPostrender implements TrustedCallbackInterface {

  /**
   * Constructs the rDcfLazyloadPostrender class.
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['dcfLazyloadPostrender'];
  }

  /**
   * Post-render callback function.
   *
   * @param string $markup
   *   The rendered element.
   * @param array $element
   *   The element which was rendered (for reference)
   *
   * @return string
   *   Markup altered as necessary.
   */
  public static function dcfLazyloadPostrender($markup, array $element) {
    // Generate attributes object for <noscript> element.
    $no_script_attributes = new Attribute();
    foreach ($element['#attributes'] as $attr_name => $attr_value) {
      $no_script_attributes[$attr_name] = $attr_value;
    }

    $no_script_attributes['src'] = $element['#attributes']['data-src'];
    unset($no_script_attributes['data-src']);
    $no_script_attributes['srcset'] = $element['#attributes']['data-srcset'];
    unset($no_script_attributes['data-srcset']);

    $no_script_attributes['alt'] = $element['#alt'];

    // Remove DCF Lazy Loading classes.
    $class_remove = [
      'dcf-lazy-load',
    ];
    $no_script_attributes['class'] = array_diff($no_script_attributes['class']->value(), $class_remove);

    // Set wrapper classes.
    $height = $element['#attributes']['height'];
    $width = $element['#attributes']['width'];
    $ratio = round($width / $height, 2);

    $wrapper_attributes = new Attribute();
    $wrapper_attributes['class'] = ['dcf-ratio'];
    $style_string = '';

    switch ($ratio) {
      case 1.78:
        $wrapper_attributes['class'][] = 'dcf-ratio-16x9';
        break;

      case 0.56:
        $wrapper_attributes['class'][] = 'dcf-ratio-9x16';
        break;

      case 1.33:
        $wrapper_attributes['class'][] = 'dcf-ratio-4x3';
        break;

      case 0.75:
        $wrapper_attributes['class'][] = 'dcf-ratio-3x4';
        break;

      case 1.00:
        $wrapper_attributes['class'][] = 'dcf-ratio-1x1';
        break;

      // If the ratio isn't standard, then calculate and handle with an inline
      // <style> element.
      default:
        $percentage = round($height / $width * 100, 2);
        $random = new Random();
        $class = $random->word(8);
        $style_string = '<style>.' . $class . '::before { padding-top: ' . $percentage . '%!important; }</style>';
        $wrapper_attributes['class'][] = $class;
    }

    return $style_string . '<div' . $wrapper_attributes . '>' . $markup . '<noscript><img' . $no_script_attributes . '></noscript></div>';
  }

}
