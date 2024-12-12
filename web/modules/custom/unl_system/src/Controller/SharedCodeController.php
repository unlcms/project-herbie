<?php
namespace Drupal\unl_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns HTML snippet output at /sharedcode/% paths to
 *   mimic files that once existed on static UNL templated sites.
 *
 * See https://drupal.stackexchange.com/a/307065
 */
class SharedCodeController extends ControllerBase {

  /**
   * Returns HTML snippets for /sharedcode/% requests.
   *
   * @param $file
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getRegion($file) {
    $region = null;

    switch ($file) {
      case 'footerContactInfo.html':
      case 'contact_us':
        $region = 'contactinfo';
        break;
      case 'navigation.html':
      case 'navigation_links':
        $region = 'navlinks';
        break;
      case 'relatedLinks.html':
      case 'related_links':
        $region = 'leftcollinks';
        break;
      default:
        throw new NotFoundHttpException();
    }

    // Switch to anonymous user.
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::getAnonymousUser());

    $content = $this->renderRegion($region);
    $html = \Drupal::service('renderer')->renderRoot($content)->__toString();

    // Convert /path links to full absolute URLs.
    $host = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $html = str_replace('href="/', 'href="'.$host, $html);
    // Dirty hack to remove the block <div> wrapper so its a plain <ul>.
    if ($region == 'navlinks') {
      $html = str_replace('<div id="block-unl-five-herbie-mainnavigation">', '', $html);
      $html = str_replace('</div>', '', $html);
    }

    $content = Markup::create($html);
    $response = new Response();
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');
    $response->headers->set('Content-Language', 'en');
    $response->setContent($content);

    // Switch user back.
    $account_switcher->switchBack();

    return $response;
  }

  /**
   * @param string $region
   */
  private function renderRegion($region) {
    $build = [];
    foreach ($this->getBlocksInRegion($region) as $block) {
      $build[] = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);
    }
    return $build;
  }

  /**
   * @param string $region
   *
   * @return array
   */
  private function getBlocksInRegion($region) {
    return \Drupal::entityTypeManager()
      ->getStorage('block')
      ->loadByProperties([
        'status' => 1,
        'theme' => 'unl_five_herbie',
        'region' => $region,
      ]);
  }
}
