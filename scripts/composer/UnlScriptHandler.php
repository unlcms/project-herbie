<?php

namespace DrupalProject\composer;

use Composer\Script\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * UNL-specific composer scripts.
 */
class UnlScriptHandler {

  /**
   * Deploys the wdn directory to the correct path.
   */
  public static function deployWdn(Event $event) {
    $io = $event->getIO();

    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    if (!$fs->exists($drupalRoot . '/wdn')) {
      $io->writeError('<error>WDN directory is missing</error>.');
    }

    $fs->mirror($drupalRoot . '/wdn/wdn', $drupalRoot . '/wdn-temp');
    $fs->remove($drupalRoot . '/wdn');
    $fs->rename($drupalRoot . '/wdn-temp', $drupalRoot . '/wdn');
    $io->write("WDN directory deployed at " . $drupalRoot . "/wdn");

  }

}
