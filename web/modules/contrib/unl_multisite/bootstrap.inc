<?php

use Drupal\Core\DrupalKernel;

require_once 'core/includes/bootstrap.inc';

unl_bootstrap($request);


function unl_bootstrap($request) {
  unl_bootstrap_multisite_without_symlinks($request);
}

function unl_bootstrap_multisite_without_symlinks($request) {
  $original_script_name = $request->server->get('SCRIPT_NAME');
  $php_file = basename($original_script_name);

  $request_uri = parse_url($request->server->get('REQUEST_URI'));
  $path_parts = explode('/', $request_uri['path']);
  foreach ($path_parts as $path_index => $path_part) {
    if (!$path_part) {
      unset($path_parts[$path_index]);
    }
  }

  $previous_conf_path = '';
  $previous_script_name = '';
  for ($i = count($path_parts); $i >= 0; $i--) {
    if ($i == 0) {
      $script_name = '/' . $php_file;
    }
    else {
      $script_name = '/' . implode('/', array_slice($path_parts, 0, $i)) . '/' . $php_file;
    }
    $request->server->set('SCRIPT_NAME', $script_name);
    $conf_path = DrupalKernel::findSitePath($request);

    if ($previous_conf_path && ($conf_path != $previous_conf_path)) {
      $request->server->set('SCRIPT_NAME', $previous_script_name);
      break;
    }

    if ($request->server->get('SCRIPT_NAME') == $original_script_name) {
      break;
    }
    $previous_conf_path = $conf_path;
    $previous_script_name = $request->server->get('SCRIPT_NAME');
  }
}
