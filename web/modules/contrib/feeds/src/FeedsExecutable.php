<?php

namespace Drupal\feeds;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds\Event\CleanEvent;
use Drupal\feeds\Event\EventDispatcherTrait;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Exception;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a feeds executable class.
 */
class FeedsExecutable implements FeedsExecutableInterface, ContainerInjectionInterface {

  use DependencySerializationTrait;
  use EventDispatcherTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new FeedsExecutable object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, AccountSwitcherInterface $account_switcher, MessengerInterface $messenger) {
    $this->setEventDispatcher($event_dispatcher);
    $this->accountSwitcher = $account_switcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('account_switcher'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem(FeedInterface $feed, $stage, array $params = []) {
    // Make sure that the feed type exists.
    $feed->getType();

    $switcher = $this->switchAccount($feed);

    try {
      switch ($stage) {
        case static::BEGIN:
          $this->import($feed);
          break;

        case static::FETCH:
          $this->doFetch($feed);
          break;

        case static::PARSE:
          $this->doParse($feed, $params['fetcher_result']);
          break;

        case static::PROCESS:
          $this->doProcess($feed, $params['item']);
          break;

        case static::CLEAN:
          $this->doClean($feed);
          break;

        case static::FINISH:
          $this->finish($feed, $params['fetcher_result']);
          break;
      }
    }
    catch (Exception $exception) {
      return $this->handleException($feed, $stage, $params, $exception);
    }
    finally {
      $switcher->switchBack();
    }
  }

  /**
   * Creates a new batch object.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to run a batch for.
   * @param string $stage
   *   The stage of the batch to run.
   *
   * @return \Drupal\feeds\FeedsBatchInterface
   *   A feeds batch object.
   */
  protected function createBatch(FeedInterface $feed, $stage) {
    return new FeedsDirectBatch($this, $feed, $stage);
  }

  /**
   * Safely switches to another account.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed that has the account to switch to.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   The account switcher to call switchBack() on.
   *
   * @see \Drupal\Core\Session\AccountSwitcherInterface::switchTo()
   */
  protected function switchAccount(FeedInterface $feed) {
    $account = new AccountProxy($this->getEventDispatcher());
    $account->setInitialAccountId($feed->getOwnerId());
    return $this->accountSwitcher->switchTo($account);
  }

  /**
   * Handles an exception during importing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param string $stage
   *   The stage which the import is at.
   * @param array $params
   *   Parameters relevant to current stage.
   * @param \Exception $exception
   *   The exception that was thrown.
   *
   * @throws \Exception
   *   Thrown if the exception should not be ignored.
   */
  protected function handleException(FeedInterface $feed, $stage, array $params, Exception $exception) {
    $feed->finishImport();

    if ($exception instanceof EmptyFeedException) {
      return;
    }
    if ($exception instanceof RuntimeException) {
      $this->messenger->addError($exception->getMessage());
      return;
    }

    throw $exception;
  }

  /**
   * Begin an import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to perform an import on.
   */
  protected function import(FeedInterface $feed) {
    try {
      $feed->lock();
    }
    catch (LockException $e) {
      $this->messenger->addWarning($this->t('The feed became locked before the import could begin.'));
      return;
    }

    $feed->clearStates();
    $this->createBatch($feed, static::FETCH)
      ->addOperation(static::FETCH)
      ->run();
  }

  /**
   * Invokes the fetch stage.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to fetch.
   */
  protected function doFetch(FeedInterface $feed) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'fetch'));
    $fetch_event = $this->dispatchEvent(FeedsEvents::FETCH, new FetchEvent($feed));
    $feed->setState(StateInterface::PARSE, NULL);

    $feed->saveStates();
    $this->createBatch($feed, static::PARSE)
      ->addOperation(static::PARSE, ['fetcher_result' => $fetch_event->getFetcherResult()])
      ->run();
  }

  /**
   * Parses.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to perform a parse event on.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   */
  protected function doParse(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'parse'));
    $parse_event = $this->dispatchEvent(FeedsEvents::PARSE, new ParseEvent($feed, $fetcher_result));

    $feed->saveStates();

    $batch = $this->createBatch($feed, static::PROCESS);
    foreach ($parse_event->getParserResult() as $item) {
      $batch->addOperation(static::PROCESS, ['item' => $item]);
    }

    // Add a final item that finalizes the import.
    $batch->addOperation(static::FINISH, ['fetcher_result' => $fetcher_result]);
    $batch->run();
  }

  /**
   * Processes an item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to perform a process event on.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to import.
   */
  protected function doProcess(FeedInterface $feed, ItemInterface $item) {
    $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'process'));
    $this->dispatchEvent(FeedsEvents::PROCESS, new ProcessEvent($feed, $item));

    $feed->saveStates();
  }

  /**
   * Cleans an entity.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to perform a clean event on.
   */
  protected function doClean(FeedInterface $feed) {
    $state = $feed->getState(StateInterface::CLEAN);

    $entity = $state->nextEntity();
    if ($entity) {
      $this->dispatchEvent(FeedsEvents::INIT_IMPORT, new InitEvent($feed, 'clean'));
      $this->dispatchEvent(FeedsEvents::CLEAN, new CleanEvent($feed, $entity));
    }

    if (!$state->count()) {
      $state->setCompleted();
    }

    $feed->saveStates();
  }

  /**
   * Finalizes the import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed which import batch is about to be finished.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The last fetcher result.
   *
   * @return bool
   *   True if the last batch was done. False if the import is still ongoing.
   */
  protected function finish(FeedInterface $feed, FetcherResultInterface $fetcher_result) {
    // Update item count.
    $feed->save();

    if ($feed->progressParsing() !== StateInterface::BATCH_COMPLETE) {
      $this->createBatch($feed, static::PARSE)
        ->addOperation(static::PARSE, ['fetcher_result' => $fetcher_result])
        ->run();
      return FALSE;
    }
    elseif ($feed->progressFetching() !== StateInterface::BATCH_COMPLETE) {
      $this->createBatch($feed, static::FETCH)
        ->addOperation(static::FETCH)
        ->run();
      return FALSE;
    }
    elseif ($feed->progressCleaning() !== StateInterface::BATCH_COMPLETE) {
      $clean_state = $feed->getState(StateInterface::CLEAN);

      $batch = $this->createBatch($feed, static::CLEAN);
      for ($i = 0; $i < $clean_state->count(); $i++) {
        $batch->addOperation(static::CLEAN);
      }

      // Add a final item that finalizes the import.
      $batch->addOperation(static::FINISH, ['fetcher_result' => $fetcher_result]);
      $batch->run();
      return FALSE;
    }
    else {
      $feed->finishImport();
      return TRUE;
    }
  }

}
