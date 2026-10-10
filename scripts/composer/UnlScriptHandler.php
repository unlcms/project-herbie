<?php

namespace DrupalProject\composer;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * UNL-specific composer package scripts.
 */
class UnlScriptHandler {

  /**
   * Deploys the wdn directory to the correct path.
   *
   * @param \Composer\Script\Event $event
   *   Event.
   */
  public static function deployWdn(Event $event) {
    $io = $event->getIO();

    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $composerRoot = $drupalFinder->getComposerRoot();

    // Symlink /vendor/unl/wdntemplates to web/wdn.
    $fs->symlink('../vendor/unl/wdntemplates/wdn', 'web/wdn');
    $io->write("WDN directory symlinked at " . $drupalRoot . "/wdn");

    // Execute git pull (composer may have installed from cache).
    $io->write("Excecuting git pull at " . $composerRoot . "/vendor/unl/wdntemplates");
    system("cd $composerRoot/vendor/unl/wdntemplates; git pull");

    // Check if Node.js is installed and meets the WDN requirement.
    if (empty(exec("which node"))) {
      $io->write("Node.js is not installed");
      return;
    }

    $wdnPackageJson = $composerRoot . '/vendor/unl/wdntemplates/package.json';
    $wdnPackage = json_decode((string) file_get_contents($wdnPackageJson), TRUE);
    $nodeRequirement = $wdnPackage['engines']['node'] ?? NULL;
    if (is_string($nodeRequirement)) {
      $nodeVersion = exec("node --version");
      if (!Semver::satisfies(ltrim($nodeVersion, 'v'), $nodeRequirement)) {
        $io->writeError("<error>Node.js {$nodeRequirement} is required in your environment. Please install node { $nodeRequirement } </error>");
        return;
      }
    }

    // Check if NPM is installed.
    if (empty(exec("which npm"))) {
      $io->write("NPM is not installed");
      return;
    }

    // Install NPM project.
    $io->write("Installing Node project at " . $composerRoot . "/vendor/unl/wdntemplates");
    system("cd $composerRoot/vendor/unl/wdntemplates; npm ci");

    // Build NPM project.
    $io->write("Running npm run build for Node project at " . $composerRoot . "/vendor/unl/wdntemplates");
    system("cd $composerRoot/vendor/unl/wdntemplates; npm run build");
  }

  /**
   * Returns the package name associated with $event.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Package event.
   *
   * @return string
   *   Package name
   *
   * @see https://stackoverflow.com/questions/47046250/how-do-you-get-the-package-name-from-a-composer-event/47065343#47065343
   */
  public static function getPackageName(PackageEvent $event) {
    /** @var InstallOperation|UpdateOperation $operation */
    $operation = $event->getOperation();

    $package = method_exists($operation, 'getPackage')
      ? $operation->getPackage()
      : $operation->getInitialPackage();

    return $package->getName();
  }

}
