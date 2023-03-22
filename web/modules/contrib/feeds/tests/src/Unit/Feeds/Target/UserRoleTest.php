<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\Feeds\Target\UserRole;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Target\TargetInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\user\RoleInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\UserRole
 * @group feeds
 */
class UserRoleTest extends ConfigEntityReferenceTestBase {

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected static $pluginId = 'user_role';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->transliteration->transliterate('Bar', LanguageInterface::LANGCODE_DEFAULT, '_')
      ->willReturn('Bar');

    // Create a role.
    $foo_role = $this->prophesize(RoleInterface::class);
    $foo_role->label()->willReturn('Foo');

    // Entity storage (needed for entity queries).
    $this->entityStorage = $this->prophesize(RoleStorageInterface::class);
    $this->entityStorage->loadMultiple()->willReturn([
      RoleInterface::ANONYMOUS_ID => $this->createMock(RoleInterface::class),
      RoleInterface::AUTHENTICATED_ID => $this->createMock(RoleInterface::class),
      'foo' => $foo_role->reveal(),
    ]);
    $this->entityTypeManager->getStorage('user_role')->willReturn($this->entityStorage);

    $this->typedConfigManager->getDefinition('user.role.*')->willReturn([
      'label' => 'User role settings',
      'mapping' => [
        'uuid' => [
          'type' => 'uuid',
          'label' => 'UUID',
        ],
        'id' => [
          'type' => 'string',
          'label' => 'ID',
        ],
        'label' => [
          'type' => 'label',
          'label' => 'Label',
        ],
      ],
    ]);

    $this->buildContainer();
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin(array $configuration = []): TargetInterface {
    $configuration += [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $this->createTargetDefinitionMock(),
      'reference_by' => 'label',
    ];
    return new UserRole($configuration, static::$pluginId, [], $this->entityTypeManager->reveal(), $this->entityFinder->reveal(), $this->transliteration->reveal(), $this->typedConfigManager->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return UserRole::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityStorageClass() {
    return RoleStorageInterface::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getReferencableEntityTypeId() {
    return 'user_role';
  }

  /**
   * {@inheritdoc}
   */
  protected function createReferencableEntityType() {
    $referenceable_entity_type = $this->prophesize(ConfigEntityTypeInterface::class);
    $referenceable_entity_type->entityClassImplements(ConfigEntityInterface::class)->willReturn(TRUE)->shouldBeCalled();
    $referenceable_entity_type->getKey('label')->willReturn('label');
    $referenceable_entity_type->getConfigPrefix()->willReturn('user.role');
    $this->entityTypeManager->getDefinition('user_role')->willReturn($referenceable_entity_type)->shouldBeCalled();

    return $referenceable_entity_type;
  }

  /**
   * Tests finding a role by label.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   */
  public function testPrepareValue() {
    $this->entityFinder->findEntities($this->getReferencableEntityTypeId(), 'label', 'Foo')
      ->willReturn(['foo'])
      ->shouldBeCalled();

    $method = $this->getProtectedClosure($this->instantiatePlugin(), 'prepareValue');
    $values = ['target_id' => 'Foo'];
    $method(0, $values);
    $this->assertSame($values, ['target_id' => 'foo']);
  }

  /**
   * Tests prepareValue() method without match.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   */
  public function testPrepareValueReferenceNotFound() {
    $this->entityFinder->findEntities($this->getReferencableEntityTypeId(), 'label', 'Bar')
      ->willReturn([])
      ->shouldBeCalled();

    $method = $this->getProtectedClosure($this->instantiatePlugin(), 'prepareValue');
    $values = ['target_id' => 'Bar'];
    $this->expectException(ReferenceNotFoundException::class);
    $this->expectExceptionMessage("The role <em class=\"placeholder\">Bar</em> cannot be assigned because it does not exist.");
    $method(0, $values);
  }

  /**
   * Tests referencing a non-allowed role.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   */
  public function testPrepareValueNonAllowedRole() {
    $this->entityFinder->findEntities($this->getReferencableEntityTypeId(), 'label', 'Foo')
      ->willReturn(['foo'])
      ->shouldBeCalled();

    // The 'Foo' role may not be used.
    $target_plugin = $this->instantiatePlugin([
      'allowed_roles' => ['foo' => FALSE],
    ]);

    $method = $this->getProtectedClosure($target_plugin, 'prepareValue');
    $values = ['target_id' => 'Foo'];
    $this->expectException(TargetValidationException::class, 'The role <em class=\"placeholder\">foo</em> may not be referenced.');
    $method(0, $values);
  }

  /**
   * Tests referencing a newly created role.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   * @covers ::createRole
   */
  public function testPrepareValueWithNewRole() {
    $this->entityFinder->findEntities($this->getReferencableEntityTypeId(), 'label', 'Bar')
      ->willReturn([])
      ->shouldBeCalled();

    $role = $this->prophesize(RoleInterface::class);
    $role->save()->willReturn(TRUE);
    $role->id()->willReturn('bar');
    $this->entityStorage->create(['id' => 'bar', 'label' => 'Bar'])
      ->willReturn($role->reveal())
      ->shouldBeCalled();

    $target_plugin = $this->instantiatePlugin([
      'autocreate' => TRUE,
    ]);

    $method = $this->getProtectedClosure($target_plugin, 'prepareValue');
    $values = ['target_id' => 'Bar'];
    $method(0, $values);
    $this->assertSame($values, ['target_id' => 'bar']);
  }

  /**
   * Tests prepareValue() with passing a space as value.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   * @covers ::createRole
   */
  public function testPrepareValueEmptyFeedWithAutoCreateRole() {
    $target_plugin = $this->instantiatePlugin([
      'autocreate' => TRUE,
    ]);

    $method = $this->getProtectedClosure($target_plugin, 'prepareValue');
    $values = ['target_id' => ' '];
    $this->expectException(EmptyFeedException::class);
    $method(0, $values);
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummary() {
    $expected = [
      'Reference by: <em class="placeholder">Label</em>',
      'Allowed roles: <em class="placeholder">Foo</em>',
      'Only assign existing roles',
      'Revoke roles: no',
    ];
    $summary = $this->instantiatePlugin()->getSummary();
    foreach ($summary as $key => $value) {
      $summary[$key] = (string) $value;
    }
    $this->assertEquals($expected, $summary);
  }

}
