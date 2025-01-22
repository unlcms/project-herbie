<?php

// Put the logo path into JavaScript for the live preview.
drupal_add_js(array('color' => array('logo' => theme_get_setting('logo', 'nic'))), 'setting');

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'top' => t('Header top'),
    'bottom' => t('Header bottom'),
    'headerlink' => t('Header links'),
    'stripestart' => t('Stripe start'),
    'stripeend' => t('Stripe End'),
    'bg' => t('Main background'),
    'text' => t('Text color'),
    'link' => t('Link color'),
    'linkactive' => t('Link color (active, visited)'),
    'footer' => t('Footer background'),
    'footertext' => t('Footer text'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('School Bus'),
      'colors' => array(
        'top' => '#616265',
        'bottom' => '#616266',
        'headerlink' => '#dfe0e0',
        'stripestart' => '#d00001',
        'stripeend' => '#40b7e8',
        'bg' => '#ffffff',
        'text' => '#616161',
        'link' => '#d00000',
        'linkactive' => '#9d0000',
        'footer' => '#616267',
        'footertext' => '#dfe0e1',
      ),
    ),
    'almostcool' => array(
      'title' => t('Almost Cool'),
      'colors' => array(
        'top' => '#e70202',
        'bottom' => '#cf2525',
        'headerlink' => '#f6f6f2',
        'stripestart' => '#1e1e1e',
        'stripeend' => '#5e5e5e',
        'bg' => '#ffffff',
        'text' => '#3b3b3b',
        'link' => '#d6121f',
        'linkactive' => '#9d0000',
        'footer' => '#1f1d1c',
        'footertext' => '#ffffff',
      ),
    ),
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'css/colors.css',
  ),

  // Files to copy.
  'copy' => array(
    'logo.png',
  ),

  // Gradient definitions.
  'gradients' => array(
    array(
      // (x, y, width, height).
      'dimension' => array(0, 0, 0, 0),
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      'colors' => array('top', 'bottom'),
    ),
  ),

  // Color areas to fill (x, y, width, height).
  'fill' => array(),

  // Coordinates of all the theme slices (x, y, width, height)
  // with their filename as used in the stylesheet.
  'slices' => array(),

  // Reference color used for blending. Matches the base.png's colors.
  'blend_target' => '#ffffff',

  // Preview files.
  'preview_css' => 'color/preview.css',
  'preview_js' => 'color/preview.js',
  'preview_html' => 'color/preview.html',

  // Base file for image generation.
  'base_image' => 'color/base.png',
);
