<?php

namespace Drupal\Tests\feeds\Unit\Controller;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Controller\SubscriptionController;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\feeds\Controller\SubscriptionController
 * @group feeds
 */
class SubscriptionControllerTest extends UnitTestCase {

  /**
   * The controller under test.
   *
   * @var \Drupal\feeds\Controller\SubscriptionController
   */
  protected $controller;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The subscription entity.
   *
   * @var \Drupal\feeds\SubscriptionInterface
   */
  protected $subscription;

  /**
   * The key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $kv;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->request = new Request();
    $this->request->query->set('hub_mode', 'subscribe');
    $this->request->query->set('hub_challenge', '1234');
    $this->request->query->set('hub_topic', 'http://example.com');

    $this->feed = $this->prophesize('Drupal\feeds\FeedInterface');

    $this->subscription = $this->prophesize('Drupal\feeds\SubscriptionInterface');
    $this->subscription->getTopic()->willReturn('http://example.com');
    $this->subscription->getState()->willReturn('subscribing');
    $this->subscription->setState(Argument::type('string'))->willReturn(NULL);
    $this->subscription->setLease(10)->willReturn(TRUE);
    $this->subscription->save()->willReturn(NULL);
    $this->subscription->id()->willReturn(1);
    $this->subscription->getToken()->willReturn('valid_token');

    $subscription_storage = $this->prophesize('Drupal\Core\Entity\EntityStorageInterface');
    $subscription_storage->load(1)->willReturn($this->subscription->reveal());
    $subscription_storage->load(2)->willReturn(FALSE);

    $feed_storage = $this->prophesize('Drupal\Core\Entity\EntityStorageInterface');
    $feed_storage->load(1)->willReturn($this->feed->reveal());

    $this->entityTypeManager = $this->prophesize('Drupal\Core\Entity\EntityTypeManagerInterface');

    $this->entityTypeManager->getStorage('feeds_subscription')->willReturn($subscription_storage->reveal());
    $this->entityTypeManager->getStorage('feeds_feed')->willReturn($feed_storage->reveal());

    $this->kv = $this->prophesize('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $kv_factory = $this->prophesize('Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface');
    $kv_factory->get('feeds_push_unsubscribe')->willReturn($this->kv->reveal());

    $this->controller = new SubscriptionController($kv_factory->reveal(), $this->entityTypeManager->reveal());
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreate() {
    $container = new ContainerBuilder();
    $container->set('keyvalue.expirable', $this->prophesize('Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface')->reveal());
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());

    $this->assertInstanceOf('Drupal\feeds\Controller\SubscriptionController', SubscriptionController::create($container));
  }

  /**
   * @covers ::subscribe
   * @covers ::handleSubscribe
   */
  public function testSubscribe() {
    $this->request->query->set('hub_lease_seconds', 10);
    $response = $this->controller->subscribe(1, 'valid_token', $this->request);
    $this->assertSame('1234', $response->getContent());
  }

  /**
   * @covers ::subscribe
   * @covers ::handleUnsubscribe
   */
  public function testUnsubscribe() {
    $this->request->query->set('hub_mode', 'unsubscribe');

    $this->kv->get('valid_token:1')->willReturn(TRUE);
    $this->kv->delete('valid_token:1')->willReturn(TRUE);

    $response = $this->controller->subscribe(1, 'valid_token', $this->request);

    $this->assertSame('1234', $response->getContent());
  }

  /**
   * @covers ::subscribe
   */
  public function testMissingChallenge() {
    $this->request->query->set('hub_challenge', NULL);
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'valid_token', $this->request);
  }

  /**
   * @covers ::subscribe
   */
  public function testMissingTopic() {
    $this->request->query->set('hub_topic', NULL);
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'valid_token', $this->request);
  }

  /**
   * @covers ::subscribe
   */
  public function testWrongMode() {
    $this->request->query->set('hub_mode', 'woops');
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'valid_token', $this->request);
  }

  /**
   * @covers ::handleSubscribe
   */
  public function testWrongToken() {
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'not_valid_token', $this->request);
  }

  /**
   * @covers ::handleSubscribe
   */
  public function testMissingSubscription() {
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(2, 'valid_token', $this->request);
  }

  /**
   * @covers ::handleSubscribe
   */
  public function testWrongTopic() {
    $this->request->query->set('hub_topic', 'http://example.com/topic');
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'valid_token', $this->request);
  }

  /**
   * @covers ::handleSubscribe
   */
  public function testSubscriptionInWrongState() {
    $this->subscription->getState()->willReturn('unsubscribed');
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'valid_token', $this->request);
  }

  /**
   * @covers ::handleUnsubscribe
   */
  public function testSubscriptionMissingKv() {
    $this->request->query->set('hub_mode', 'unsubscribe');
    $this->request->query->set('hub_topic', 'http://example.com/topic');
    $this->expectException(NotFoundHttpException::class);
    $this->controller->subscribe(1, 'valid_token', $this->request);
  }

  /**
   * @covers ::receive
   */
  public function testReceive() {
    $payload = 'abcdefg';
    $sig = hash_hmac('sha1', $payload, 'secret');

    $request = new Request([], [], [], [], [], [], $payload);

    $request->headers->set('X-Hub-Signature', 'sha1=' . $sig);

    $this->subscription->checkSignature($sig, $payload)->willReturn(TRUE);

    $response = $this->controller->receive($this->subscription->reveal(), 'valid_token', $request);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::receive
   */
  public function testReceiveMissingSig() {
    $this->expectException(NotFoundHttpException::class);
    $this->controller->receive($this->subscription->reveal(), 'valid_token', $this->request);
  }

  /**
   * @covers ::receive
   */
  public function testReceiveBadSig() {
    $payload = 'abcdefg';
    $sig = 'oops';

    $request = new Request([], [], [], [], [], [], $payload);

    $request->headers->set('X-Hub-Signature', 'sha1=' . $sig);

    $this->subscription->checkSignature($sig, $payload)->willReturn(FALSE);

    $this->expectException(NotFoundHttpException::class);
    $this->controller->receive($this->subscription->reveal(), 'valid_token', $request);
  }

  /**
   * @covers ::receive
   */
  public function testReceiveBadToken() {
    $this->expectException(NotFoundHttpException::class);
    $this->controller->receive($this->subscription->reveal(), 'not_valid_token', $this->request);
  }

  /**
   * @covers ::receive
   */
  public function testReceiveFeedFailed() {
    $payload = 'abcdefg';
    $sig = hash_hmac('sha1', $payload, 'secret');

    $request = new Request([], [], [], [], [], [], $payload);

    $request->headers->set('X-Hub-Signature', 'sha1=' . $sig);

    $this->subscription->checkSignature($sig, $payload)->willReturn(TRUE);

    $this->feed->pushImport($payload)->willThrow(new \Exception());

    $response = $this->controller->receive($this->subscription->reveal(), 'valid_token', $request);
    $this->assertSame(500, $response->getStatusCode());
  }

}
