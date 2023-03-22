<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target {

  use Drupal\book\BookManagerInterface;
  use Drupal\Core\Database\Connection;
  use Drupal\Core\DependencyInjection\ContainerBuilder;
  use Drupal\Core\Entity\EntityFieldManagerInterface;
  use Drupal\Core\Field\FieldStorageDefinitionInterface;
  use Drupal\feeds\EntityFinderInterface;
  use Drupal\feeds\Exception\ReferenceNotFoundException;
  use Drupal\feeds\FeedTypeInterface;
  use Drupal\feeds\Feeds\Target\Book;
  use Drupal\feeds\TargetDefinition;
  use Drupal\node\NodeInterface;
  use Drupal\node\NodeStorageInterface;
  use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
  use Prophecy\Argument;

  /**
   * @coversDefaultClass \Drupal\feeds\Feeds\Target\Book
   * @group feeds
   */
  class BookTest extends FeedsUnitTestCase {

    /**
     * The ID of the plugin.
     *
     * @var string
     */
    protected static $pluginId = 'book';

    /**
     * Database Service Object.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * The node storage prophecy used in the test.
     *
     * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\node\NodeStorageInterface
     */
    protected $nodeStorage;

    /**
     * The entity field manager.
     *
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * The book manager.
     *
     * @var \Drupal\book\BookManagerInterface
     */
    protected $bookManager;

    /**
     * The entity finder used in the test.
     *
     * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\feeds\EntityFinderInterface
     */
    protected $entityFinder;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      parent::setUp();

      // Some methods in Book are using string translation.
      $container = new ContainerBuilder();
      $container->set('string_translation', $this->getStringTranslationStub());
      \Drupal::setContainer($container);

      // Create a few field definitions.
      $field_storage_definitions = $this->createMockedFieldStorageDefinitions([
        'nid' => 'ID',
        'uuid' => 'UUID',
        'vid' => 'Revision ID',
        'title' => 'Title',
        'feeds_item' => 'node.feeds_item',
      ]);

      $this->database = $this->prophesize(Connection::class);
      $this->nodeStorage = $this->prophesize(NodeStorageInterface::class);
      $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
      $this->entityFieldManager->getFieldStorageDefinitions('node')->willReturn($field_storage_definitions);
      $this->bookManager = $this->prophesize(BookManagerInterface::class);
      $this->entityFinder = $this->prophesize(EntityFinderInterface::class);
    }

    /**
     * Returns a list of mocked field storage definitions.
     *
     * @param array $fields
     *   A list of field labels, keyed by name.
     *
     * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
     *   A list of mocked field storage definitions.
     */
    protected function createMockedFieldStorageDefinitions(array $fields) {
      $definitions = [];
      foreach ($fields as $name => $label) {
        $definition = $this->prophesize(FieldStorageDefinitionInterface::class);
        $definition->getLabel()->willReturn($label);
        $definition->getType()->willReturn('string');
        $definitions[$name] = $definition->reveal();
      }
      return $definitions;
    }

    /**
     * Instantiates the FeedsTarget plugin being tested.
     *
     * @param array $configuration
     *   (optional) Configuration for the target plugin.
     *
     * @return \Drupal\feeds\Feeds\Target\Book
     *   A FeedsTarget plugin of type 'book'.
     */
    protected function getTargetPlugin(array $configuration = []) {
      $method = $this->getMethod(Book::class, 'prepareTarget')->getClosure();
      $configuration += [
        'feed_type' => $this->createMock(FeedTypeInterface::class),
        'target_definition' => $method(),
      ];

      $book_target = $this->getMockBuilder(Book::class)
        ->setConstructorArgs([
          $configuration, static::$pluginId,
          [],
          $this->database->reveal(),
          $this->nodeStorage->reveal(),
          $this->entityFieldManager->reveal(),
          $this->bookManager->reveal(),
          $this->entityFinder->reveal(),
        ])
        ->onlyMethods(['findFirstNidInBook'])
        ->getMock();

      $book_target->expects($this->any())
        ->method('findFirstNidInBook')
        ->will($this->returnCallback([__CLASS__, 'findFirstNidInBook']));

      return $book_target;
    }

    /**
     * Replaces method Book::findFirstNidInBook().
     *
     * Just return the first passed node ID.
     *
     * @param int $book_id
     *   The book to search within.
     * @param int[] $nids
     *   The node ID's to check.
     *
     * @return int|false
     *   The first node ID that is assumed to be in the book.
     */
    public static function findFirstNidInBook(int $book_id, array $nids) {
      return reset($nids);
    }

    /**
     * Sets a callback for the findEntity() method on the EntityFinder object.
     *
     * @param callable $callback
     *   The callback to set.
     * @param string $reference_by
     *   The expected field to reference by.
     */
    protected function setFindEntitiesCallback(callable $callback, string $reference_by = 'nid') {
      $this->entityFinder->findEntities('node', $reference_by, Argument::type('scalar'), [], Argument::type('bool'))
        ->will($callback)
        ->shouldBeCalled();
    }

    /**
     * @covers ::prepareTarget
     */
    public function testPrepareTarget() {
      $method = $this->getMethod(Book::class, 'prepareTarget')->getClosure();
      $this->assertInstanceof(TargetDefinition::class, $method());
    }

    /**
     * @covers ::prepareValues
     * @covers ::findEntity
     */
    public function testPrepareValues() {
      $this->setFindEntitiesCallback(function (array $args) {
        return [$args[2]];
      });

      $target = $this->getTargetPlugin();

      $values = [
        [
          'bid' => 1,
          'pid' => 9,
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $values = $method($values);
      $this->assertSame(1, $values['bid']);
      $this->assertSame(9, $values['pid']);
    }

    /**
     * @covers ::prepareValues
     * @covers ::findEntity
     */
    public function testPrepareValuesWithReferencingByTitle() {
      $this->setFindEntitiesCallback(function (array $args) {
        switch ($args[2]) {
          case 'Book Foo':
            return [1];

          case 'Chapter 1':
            return [2];
        }
      }, 'title');

      $target = $this->getTargetPlugin([
        'book_reference_by' => 'title',
        'parent_reference_by' => 'title',
      ]);

      $values = [
        [
          'bid' => 'Book Foo',
          'pid' => 'Chapter 1',
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $values = $method($values);
      $this->assertSame(1, $values['bid']);
      $this->assertSame(2, $values['pid']);
    }

    /**
     * @covers ::prepareValues
     * @covers ::findEntity
     *
     * @todo update
     */
    public function testPrepareValuesWithReferencingByGuid() {
      $this->setFindEntitiesCallback(function (array $args) {
        switch ($args[2]) {
          case 'p001':
            return [1];

          case 'p346':
            return [6];
        }
      }, 'feeds_item.guid');

      $target = $this->getTargetPlugin([
        'book_reference_by' => 'feeds_item',
        'book_feeds_item' => 'guid',
        'parent_reference_by' => 'feeds_item',
        'parent_feeds_item' => 'guid',
      ]);

      $values = [
        [
          'bid' => 'p001',
          'pid' => 'p346',
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $values = $method($values);
      $this->assertSame(1, $values['bid']);
      $this->assertSame(6, $values['pid']);
    }

    /**
     * Tests that the book ID can be taken from the parent node.
     */
    public function testTakeBidFromParentBookNode() {
      $this->setFindEntitiesCallback(function (array $args) {
        return [$args[2]];
      });

      // Make sure a mocked parent node gets returned.
      $node = $this->createMock(NodeInterface::class);
      $node->book = [
        'bid' => 3,
      ];
      $this->nodeStorage->load(9)
        ->willReturn($node)
        ->shouldBeCalled();

      $target = $this->getTargetPlugin();

      $values = [
        [
          'pid' => 9,
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $values = $method($values);
      $this->assertSame(3, $values['bid']);
      $this->assertSame(9, $values['pid']);
    }

    /**
     * Tests that the book ID is used as parent ID if no parent ID was given.
     */
    public function testUseBookIdAsParentNodeId() {
      $this->setFindEntitiesCallback(function (array $args) {
        return [$args[2]];
      });

      $target = $this->getTargetPlugin();

      $values = [
        [
          'bid' => 3,
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $values = $method($values);
      $this->assertSame(3, $values['bid']);
      $this->assertSame(3, $values['pid']);
    }

    /**
     * Tests that values are emptied when a book ID cannot be taken.
     */
    public function testPrepareValuesWithNoBid() {
      $this->setFindEntitiesCallback(function (array $args) {
        return [$args[2]];
      });

      $target = $this->getTargetPlugin();

      $values = [
        [
          'pid' => 9,
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $values = $method($values);
      $this->assertSame([], $values);
    }

    /**
     * Tests prepareValues() method without match.
     *
     * @covers ::prepareValues
     * @covers ::findEntity
     */
    public function testPrepareValuesReferenceNotFound() {
      $this->setFindEntitiesCallback(function (array $args) {
        return [];
      });

      $target = $this->getTargetPlugin();

      $values = [
        [
          'bid' => 1,
          'pid' => 9,
        ],
      ];

      $method = $this->getProtectedClosure($target, 'prepareValues');
      $this->expectException(ReferenceNotFoundException::class);
      $this->expectExceptionMessage("Referenced entity not found for field <em class=\"placeholder\">nid</em> with value <em class=\"placeholder\">1</em>.");
      $values = $method($values);
    }

  }
}

namespace {

  use Drupal\Component\Render\FormattableMarkup;

  if (!function_exists('t')) {

    /**
     * Stub for t() function.
     */
    function t($string, array $args = []) {
      return new FormattableMarkup($string, $args);
    }

  }

}
