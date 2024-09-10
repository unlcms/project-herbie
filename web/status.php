<?php

/**
 * @file
 * This page will attempt to bootstrap drupal and then print a message
 * to indicate if it was successful or not.
 */

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

if (($cache = get_cache()) !== FALSE && !array_key_exists('nocache', $_GET)) {
  $status = $cache;
}
elseif (health_check()) {
  $status['headers'] = [];
  $status['message'] = '200 Still Alive';
  set_cache($status);
}
else {
  $status['headers'] = ['HTTP/1.1 500 Internal Server Error'];
  $status['message'] = '500 Dead';
  set_cache($status);
}

header('Content-type: text/plain');
foreach ($status['headers'] as $header) {
  header($header);
}
echo $status['message'];

// Perform the actual health check.
function health_check() {
  try {
    $autoloader = require 'autoload.php';
    require_once  'core/includes/bootstrap.inc';
    $request = Request::createFromGlobals();
    require_once 'modules/contrib/unl_multisite/bootstrap.inc';
    Settings::initialize(dirname(dirname(__DIR__)), DrupalKernel::findSitePath($request), $autoloader);
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();

    return true;
  }
  catch (Exception $e) {
    return false;
  }
}

// Retrieve the cached value from shared memory (or return false).
function get_cache() {
  if (!extension_loaded('apcu')) {
    return false;
  }

  if (!apcu_exists(__FILE__ . ':status')) {
    return false;
  }

  $success = false;
  $cache = apcu_fetch(__FILE__ . ':status', $success);
  if (!$success) {
    return false;
  }

  if (!array_key_exists('time', $cache)) {
    return false;
  }

  if ($cache['time'] < time() - 120) {
    return false;
  }

  return $cache;
}

// Store the cached value into shared memory (or return false).
function set_cache($status) {
  if (!extension_loaded('apcu')) {
    return false;
  }

  $status['time'] = time();
  return apcu_store(__FILE__ .':status', $status);
}
