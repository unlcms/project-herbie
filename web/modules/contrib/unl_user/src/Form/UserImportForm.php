<?php

namespace Drupal\unl_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an example form.
 */
abstract class UserImportForm extends FormBase {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->store = $temp_store_factory->get('multistep_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the multistep form.
   */
  protected function deleteStore() {
    //We only had one key, but keep it robust in case we add more
    $keys = ['unl_import_data'];
    foreach ($keys as $key) {
      $this->store->delete($key);
    }
  }
}
