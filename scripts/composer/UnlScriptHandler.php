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

    // Check if dev dependencies are being installed.
    // WDN is deployed in a different manner on production.
    if ($event->isDevMode()) {
      $io = $event->getIO();

      $fs = new Filesystem();
      $drupalFinder = new DrupalFinder();
      $drupalFinder->locateRoot(getcwd());
      $drupalRoot = $drupalFinder->getDrupalRoot();
      $composerRoot = $drupalFinder->getComposerRoot();

      // Symlink /vendor/unl/wdntemplates to web/wdn.
      $fs->symlink('../vendor/unl/wdntemplates/wdn', 'web/wdn');
      $io->write("WDN directory symlinked at " . $drupalRoot . "/wdn");

      // Check if NPM is installed.
      if (empty(exec("which npm"))) {
        $io->write("NPM is not installed");
        return;
      }

      // Install NPM project.
      exec("cd $composerRoot/vendor/unl/wdntemplates; npm install");
      $io->write("Node project installed at " . $composerRoot . "/vendor/unl/wdntemplates");

      // Check if Grunt CLI is installed.
      if (empty(exec("which grunt"))) {
        $io->write("Grunt CLI is not installed. Run 'npm install -g grunt-cli'");
        return;
      }

      // Run Grunt default task.
      exec("cd $composerRoot/vendor/unl/wdntemplates; grunt");
      $io->write("Grunt default task run at " . $composerRoot . "/vendor/unl/wdntemplates");
    }

  }

}
