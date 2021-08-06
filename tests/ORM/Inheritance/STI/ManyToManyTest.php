<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Inheritance\STI;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Inheritance\Fixture\RbacItemAbstract;
use Cycle\ORM\Tests\Inheritance\Fixture\RbacPermission;
use Cycle\ORM\Tests\Inheritance\Fixture\RbacRole;
use stdClass;

abstract class ManyToManyTest extends StiBaseTest
{
    protected const PARENT_MAPPER = Mapper::class;
    protected const CHILD_MAPPER = StdMapper::class;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('rbac_item', [
            'name' => 'string,primary',
            'description' => 'string,nullable',
            '_type' => 'string,nullable',
        ]);

        $this->makeTable('rbac_item_inheritance', [
            'id' => 'primary',
            'parent' => 'string',
            'child' => 'string',
        ]);

        $this->makeFK('rbac_item_inheritance', 'parent', 'rbac_item', 'name', 'NO ACTION', 'NO ACTION');
        $this->makeFK('rbac_item_inheritance', 'child', 'rbac_item', 'name', 'NO ACTION', 'NO ACTION');

        $this->withSchema(new Schema($this->getSchemaArray()));
    }

    protected function getSchemaArray(): array
    {
        return [
            RbacItemAbstract::class => [
                SchemaInterface::ROLE => 'rbac_item',
                SchemaInterface::CHILDREN => [
                    'role' => RbacRole::class,
                    'permission' => RbacPermission::class,
                ],
                SchemaInterface::MAPPER => static::PARENT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'rbac_item',
                SchemaInterface::PRIMARY_KEY => 'name',
                SchemaInterface::COLUMNS => ['name', 'description', '_type'],
                SchemaInterface::RELATIONS => [
                    'parents' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::COLLECTION_TYPE => 'doctrine',
                        Relation::TARGET => 'rbac_item',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'rbac_item_inheritance',
                            Relation::INNER_KEY => 'name',
                            Relation::OUTER_KEY => 'name',
                            Relation::THROUGH_INNER_KEY => 'child',
                            Relation::THROUGH_OUTER_KEY => 'parent',
                            Relation::HANDSHAKE => 'children',
                        ],
                    ],
                    'children' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::COLLECTION_TYPE => 'doctrine',
                        Relation::TARGET => 'rbac_item',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'rbac_item_inheritance',
                            Relation::INNER_KEY => 'name',
                            Relation::OUTER_KEY => 'name',
                            Relation::THROUGH_INNER_KEY => 'parent',
                            Relation::THROUGH_OUTER_KEY => 'child',
                            Relation::HANDSHAKE => 'parents',
                        ],
                    ],
                ],
            ],
            RbacRole::class => [
                SchemaInterface::ROLE => 'rbac_role',
            ],
            RbacPermission::class => [
                SchemaInterface::ROLE => 'rbac_permission',
            ],
            'rbac_item_inheritance' => [
                SchemaInterface::ROLE => 'rbac_item_inheritance',
                SchemaInterface::MAPPER => static::CHILD_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'rbac_item_inheritance',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'parent', 'child'],
                SchemaInterface::RELATIONS => [],
            ],
        ];
    }

    public function testStore(): void
    {
        $role = new RbacRole('superAdmin');

        $permission = new RbacPermission('writeUser');

        $role->children->add($permission);
        $permission->parents->add($role);

        $this->save($role);

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm->withHeap(new Heap()), 'rbac_item'))
            ->load('children')
            ->wherePK('superAdmin')->fetchOne();

        self::assertInstanceOf(RbacRole::class, $fetchedRole);
        self::assertCount(1, $fetchedRole->children);
        self::assertInstanceOf(RbacPermission::class, $fetchedRole->children->first());
        self::assertSame('writeUser', $fetchedRole->children->first()->name);
    }

    public function testClearAndFillRelation(): void
    {
        $role = new RbacRole('superAdmin');
        $permission = new RbacPermission('writeUser');

        $role->children->add($permission);
        $permission->parents->add($role);

        $this->save($role);

        unset($role, $permission);

        $this->orm = $this->orm->withHeap(new Heap());

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm, 'rbac_item'))
            ->load('children')
            ->wherePK('superAdmin')->fetchOne();
        /** @var RbacPermission $fetchedPermission */
        $fetchedPermission = (new Select($this->orm, 'rbac_item'))
            ->load('parents')
            ->wherePK('writeUser')->fetchOne();

        $fetchedRole->children->removeElement($fetchedPermission);
        $fetchedPermission->parents->removeElement($fetchedRole);

        $this->save($fetchedRole);

        $fetchedRole->children->add($fetchedPermission);
        // Should be solved with proxy task
        $fetchedPermission->parents->add($fetchedRole);

        $this->save($fetchedRole);

        self::assertTrue(true);
    }

    public function testMakeEntityUsingRole(): void
    {
        $this->assertInstanceOf(RbacRole::class, $this->orm->make('rbac_role'));
        $this->assertInstanceOf(RbacPermission::class, $this->orm->make('rbac_permission'));
        $this->assertInstanceOf(stdClass::class, $this->orm->make('rbac_item_inheritance'));
    }

    public function testMakeUndefinedChildRole(): void
    {
        $mapper = $this->orm->getMapper('rbac_item');

        $this->expectException(\InvalidArgumentException::class);

        $mapper->init([], 'some_undefined_role');
    }

    public function testNotTriggersRehydrate(): void
    {
        $role = new RbacRole('superAdmin', 'description');

        $permission = new RbacPermission('writeUser');

        $role->children->add($permission);
        $permission->parents->add($role);

        $this->save($role);

        unset($role, $permission);

        $this->orm = $this->orm->withHeap(new Heap());

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm, 'rbac_item'))
            ->wherePK('superAdmin')->load('children')->fetchOne();
        /** @var RbacPermission $fetchedPermission */
        $fetchedPermission = (new Select($this->orm, 'rbac_item'))
            ->wherePK('writeUser')->load('parents')->fetchOne();

        $fetchedRole->description = 'updated description';

        // unlink
        $fetchedRole->children->removeElement($fetchedPermission);
        $fetchedPermission->parents->removeElement($fetchedRole);

        self::assertSame('updated description', $fetchedRole->description);

        $this->orm = $this->orm->withHeap(new Heap());
    }
}
