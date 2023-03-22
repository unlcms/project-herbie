<?php

namespace Drupal\Tests\feeds\Kernel\Lock;

use Drupal\feeds\FeedsExecutableInterface;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Lock\FeedsLockBackend
 * @group feeds
 */
class FeedsLockBackendTest extends FeedsKernelTestBase {

  /**
   * The default setting for the lock timeout.
   *
   * @var int
   */
  const DEFAULT_LOCK_TIMEOUT = 43200;

  /**
   * The Feeds Lock service.
   *
   * @var \Drupal\feeds\Lock\FeedsLockBackend
   */
  protected $lock;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\Entity\Feed
   */
  protected $feed;

  /**
   * The name of the lock used when locking the feed.
   *
   * @var string
   */
  protected $lockName;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->lock = $this->container->get('feeds.lock');
    $this->feedType = $this->createFeedType();
    $this->feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    $this->lockName = 'feeds_feed:' . $this->feed->id();
  }

  /**
   * Gets the expire time for the requested lock name.
   *
   * @param string $name
   *   The lock to get the expire time for.
   *
   * @return int|null
   *   The expire timestamp or null if the requested lock does not exist.
   */
  protected function getExpireTime(string $name): ?int {
    $result = $this->container->get('database')
      ->select('semaphore')
      ->fields('semaphore', ['expire'])
      ->condition('name', $name)
      ->execute()
      ->fetchField();

    if ($result) {
      return (int) $result;
    }
    return NULL;
  }

  /**
   * Sets the expire time for the requested lock name.
   *
   * @param string $name
   *   The lock to set expire time for.
   * @param int $time
   *   The expire time to set.
   */
  protected function setExpireTime(string $name, int $time): void {
    $this->container->get('database')
      ->merge('semaphore')
      ->fields([
        'value' => 'persistent',
        'expire' => $time,
      ])
      ->key('name', $name)
      ->execute();
  }

  /**
   * Returns the timestamp for the current request.
   *
   * @return int
   *   A Unix timestamp.
   */
  protected function getRequestTime(): int {
    return $this->container->get('datetime.time')
      ->getRequestTime();
  }

  /**
   * Tests that a lock gets released that has expired.
   */
  public function testLockReleaseWhenExpired() {
    // Create a lock for the feed, set expire time more than an hour in the
    // past.
    $this->lock->acquire($this->lockName);
    $this->setExpireTime($this->lockName, $this->getRequestTime() - 3601);

    // Assert that the lock may be released.
    $this->assertTrue($this->lock->lockMayBeAvailable($this->lockName));
  }

  /**
   * Tests that a lock does *not* get released if not expired.
   */
  public function testNoLockReleaseWhenNotExpired() {
    $this->lock->acquire($this->lockName);
    $this->assertFalse($this->lock->lockMayBeAvailable($this->lockName));
  }

  /**
   * Tests that a lock gets released when the feed no longer exists.
   */
  public function testLockReleaseNonExistentFeed() {
    $name = 'feeds_feed:123';

    // Create a lock for a non-existing feed.
    $this->lock->acquire($name, 3600);
    // Assert that the lock is not available right away.
    $this->assertFalse($this->lock->lockMayBeAvailable($name));

    // Set expire time to be more than an hour in the past.
    $this->setExpireTime($name, $this->getRequestTime() - 3601);
    // Assert that the lock now gets released because of a non-existing feed.
    $this->assertTrue($this->lock->lockMayBeAvailable($name));
  }

  /**
   * Tests that the lock does not get released when there are queue tasks.
   */
  public function testNoLockReleaseWhenQueueTasksExist() {
    // Create a queue task for the feed.
    $this->container->get('queue')
      ->get('feeds_feed_refresh:' . $this->feed->bundle())
      ->createItem([$this->feed->id(), FeedsExecutableInterface::BEGIN, []]);

    // Create a lock for the feed, set expire time more than an hour in the
    // past.
    $this->lock->acquire($this->lockName, 3600);
    $this->setExpireTime($this->lockName, $this->getRequestTime() - 3601);

    // Assert that the lock may not break yet, because there is still a queue
    // task.
    $this->assertFalse($this->lock->lockMayBeAvailable($this->lockName));

    // Assert that the lock has been extended.
    $this->assertGreaterThanOrEqual($this->getRequestTime() + static::DEFAULT_LOCK_TIMEOUT, $this->getExpireTime($this->lockName));
  }

  /**
   * Tests releasing a lock when only queue tasks for other feeds exists.
   *
   * This should cover the case that hasQueueTasks() doesn't return TRUE if the
   * feed in question doesn't have a queue task, but other feeds do.
   */
  public function testLockReleaseWhenQueueTasksExistForOtherFeeds() {
    // Create queue tasks for other feeds.
    for ($i = 2; $i < 100; $i++) {
      $this->container->get('queue')
        ->get('feeds_feed_refresh:' . $this->feed->bundle())
        ->createItem([$i, FeedsExecutableInterface::BEGIN, []]);
    }

    // Create a lock for the feed, set expire time more than an hour in the
    // past.
    $this->lock->acquire($this->lockName, 3600);
    $this->setExpireTime($this->lockName, $this->getRequestTime() - 3601);

    // Assert that the lock gets released because for the feed in question
    // no queue task exists.
    $this->assertTrue($this->lock->lockMayBeAvailable($this->lockName));
  }

  /**
   * Tests that the lock does not get released when there was recent progress.
   */
  public function testNoLockReleaseWithRecentProgress() {
    // Set flag for recent progress.
    \Drupal::keyValue('feeds_feed.' . $this->feed->id())->set('last_activity', time());

    // Create a lock for the feed, set expire time more than an hour in the
    // past.
    $this->lock->acquire($this->lockName, 3600);
    $this->setExpireTime($this->lockName, $this->getRequestTime() - 3601);

    // Assert that the lock may not break yet, because there was recent
    // activity.
    $this->assertFalse($this->lock->lockMayBeAvailable($this->lockName));

    // Assert that the lock has been extended.
    $this->assertGreaterThanOrEqual($this->getRequestTime() + static::DEFAULT_LOCK_TIMEOUT, $this->getExpireTime($this->lockName));
  }

  /**
   * Tests that a lock gets released when there was no recent progress.
   */
  public function testLockReleaseWithProgressLongAgo() {
    // Set flag for progress happening over an hour ago. Assumed is that an
    // import in the UI would never hang for an hour. Well, in theory, this
    // *can* still happen, but we'll have to draw a line somewhere.
    \Drupal::keyValue('feeds_feed.' . $this->feed->id())->set('last_activity', $this->getRequestTime() - 3601);

    // Create a lock for the feed, set expire time more than an hour in the
    // past.
    $this->lock->acquire($this->lockName, 3600);
    $this->setExpireTime($this->lockName, $this->getRequestTime() - 3601);

    // Assert that the lock may be released, since the last reported progress
    // was not recent enough.
    $this->assertTrue($this->lock->lockMayBeAvailable($this->lockName));
  }

  /**
   * Tests extending a lock.
   */
  public function testExtendLock() {
    // Create lock.
    $this->lock->acquire($this->lockName);

    // Try to extend the lock with 30 minutes.
    $this->assertTrue($this->lock->extendLock($this->lockName, 1800));

    // Assert that the lock has been extended.
    $this->assertGreaterThan($this->getRequestTime() + 1800, $this->getExpireTime($this->lockName));
  }

  /**
   * Tests extending a lock that does not exist.
   *
   * Extending a lock should not succeed, but there shouldn't be PHP errors
   * either.
   */
  public function testExtendNonExistingLock() {
    // Try to extend a non-existing lock.
    $this->assertFalse($this->lock->extendLock('feeds_feed:123', 1800));
  }

}
