<?php
namespace Drupal\unl_icon;

use Drupal\Core\Controller\ControllerBase;

class UnlIconController extends ControllerBase {

  /**
   * Page callback for unl_icon.showcase.
   */
  public function showcase() {
    $library_path = \Drupal::service('library.libraries_directory_file_finder')->find('icon-library');
    $optimized_path = $library_path . '/svg/optimized';
    $files = $this->scanAllDir($optimized_path);
    $options = [];

    foreach ($files as $path) {
      $name = basename($path, '.svg');
      $options[$name] = file_get_contents($library_path . '/svg/optimized/' . $path);
    }
    ksort($options);

    $markup = '<style>.dcf-w-100\% {width: 100% !important;}.dcf-h-100\% {height: 100% !important;}</style>';
    foreach ($options as $name => $option) {
      $markup .= '<div style="float:left; width:72px; height:160px; padding:8px;"><p style="padding:10px;">'
        . $option . '</p><p style="text-align:center; font-size:13px;">' . $name
        . '</p></div>';
    }

    return [
      '#type' => 'inline_template',
      '#template' => $markup,
    ];
  }

  /**
   * Returns an array of all files from a recursive scan of a directory.
   * https://stackoverflow.com/a/46697247
   *
   * @param string $dir
   *
   * @return array
   */
  function scanAllDir($dir) {
    $result = [];
    foreach(scandir($dir) as $filename) {
      if ($filename[0] === '.') continue;
      $filePath = $dir . '/' . $filename;
      if (is_dir($filePath)) {
        foreach (scanAllDir($filePath) as $childFilename) {
          $result[] = $filename . '/' . $childFilename;
        }
      }
      else {
        $result[] = $filename;
      }
    }
    return $result;
  }

}
