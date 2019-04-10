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
   * Deploys custom patches to core scaffold files.
   * 
   * @param \Composer\Installer\PackageEvent $event
   *   Package event.
   */
  public static function patchCore(PackageEvent $event) {
    $package = self::getPackageName($event);


    if ($package == 'drupal/core') {
      $io = $event->getIO();
      $fs = new Filesystem();
      $drupalFinder = new DrupalFinder();
      $drupalFinder->locateRoot(getcwd());
      $drupalRoot = $drupalFinder->getDrupalRoot();

      $io->write("Patching 'sites/default/default.settings.php'");
      $fs->chmod($drupalRoot . '/sites/default', 0777);
      $fs->chmod($drupalRoot . '/sites/default/default.settings.php', 0777);
      $patch_output = shell_exec("cd $drupalRoot; patch -p1 < ../patches/default.settings.php.patch");
      $fs->chmod($drupalRoot . '/sites/default/default.settings.php', 0644);
      $fs->chmod($drupalRoot . '/sites/default', 0755);

      if (strpos($patch_output, 'sites/default/default.settings.php.rej') !== false ) {
        $io->write('Removing ' . $drupalRoot . '/sites/default/default.settings.php.rej');
        $fs->remove($drupalRoot . '/sites/default/default.settings.php.rej');
      }
    }
  }

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

    // Check if NPM is installed.
    if (empty(exec("which npm"))) {
      $io->write("NPM is not installed");
      return;
    }

    // Install NPM project.
    $io->write("Installing Node project at " . $composerRoot . "/vendor/unl/wdntemplates");
    exec("cd $composerRoot/vendor/unl/wdntemplates; npm install");

    // Check if Grunt CLI is installed.
    if (empty(exec("which grunt"))) {
      $io->write("Grunt CLI is not installed. Run 'npm install -g grunt-cli'");
      return;
    }

    // Run Grunt default task.
    $io->write("Running Grunt default task at " . $composerRoot . "/vendor/unl/wdntemplates");
    exec("cd $composerRoot/vendor/unl/wdntemplates; grunt");
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
