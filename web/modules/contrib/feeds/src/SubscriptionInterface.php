<?php

namespace Drupal\feeds;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a feeds_subscription entity.
 */
interface SubscriptionInterface extends ContentEntityInterface {

  /**
   * Subscribes to a hub that supports pushing content.
   */
  public function subscribe();

  /**
   * Unsubscribes from a hub.
   */
  public function unsubscribe();

  /**
   * Returns a timestamp of when the subscription expires.
   *
   * @return int
   *   The UNIX timestamp when the subscription expires.
   */
  public function getExpire();

  /**
   * Returns the fully-qualified URL of the PuSH hub.
   *
   * @return string
   *   The fully-qualified URL of the PuSH hub.
   */
  public function getHub();

  /**
   * Returns the secret used to verify a request.
   *
   * @return string
   *   The secret used to verify a request.
   */
  public function getSecret();

  /**
   * Returns the feed URL.
   *
   * @return string
   *   The fully-qualified URL of the feed.
   */
  public function getTopic();

  /**
   * Returns the token that is used as part of the URL.
   *
   * @return string
   *   The token used as part of the URL.
   */
  public function getToken();

  /**
   * Returns the state of the subscription.
   *
   * The state of the subscription can be, for example:
   * - 'subscribed', which means that the subscription is active.
   *
   * @return string
   *   The state of the subscription.
   */
  public function getState();

  /**
   * Sets the state of the subscription.
   *
   * @param string $state
   *   The state to set.
   */
  public function setState($state);

  /**
   * Returns the number of seconds of the lease.
   *
   * The hub-determined number of seconds that the subscription will stay active
   * before expiring, measured from the time the verification request was made
   * from the hub to the subscriber.
   *
   * @return int
   *   The time, in seconds of the lease.
   */
  public function getLease();

  /**
   * Sets the number of seconds of the lease.
   *
   * Usually this value is retrieved from the hub where is being subscribed to
   * and then stored on the subscription entity.
   *
   * @param int $lease
   *   The time, in seconds of the lease.
   */
  public function setLease($lease);

  /**
   * Verifies that the content that was pushed comes from a verified source.
   *
   * @param string $sha1
   *   The HMAC signature from the hub.
   * @param string $data
   *   The data to hash and then compare to the hub's signature.
   *
   * @return bool
   *   True if the signature is valid, false otherwise.
   */
  public function checkSignature($sha1, $data);

}
