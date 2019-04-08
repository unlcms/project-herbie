<?php

namespace DrupalProject\composer;

use Composer\Installer\PackageEvent;
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
