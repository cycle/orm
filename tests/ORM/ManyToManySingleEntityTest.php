<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\RbacItemAbstract;
use Cycle\ORM\Tests\Fixtures\RbacPermission;
use Cycle\ORM\Tests\Fixtures\RbacRole;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class ManyToManySingleEntityTest extends BaseTest
{
    use TableTrait;

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

        $this->withSchema(new Schema([
            RbacItemAbstract::class => [
                Schema::ROLE => 'rbac_item',
                Schema::CHILDREN => [
                    'role' => RbacRole::class,
                    'permission' => RbacPermission::class,
                ],
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'rbac_item',
                Schema::PRIMARY_KEY => 'name',
                Schema::COLUMNS => ['name', 'description', '_type'],
                Schema::RELATIONS => [
                    'parents' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'rbac_item',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'rbac_item_inheritance',
                            Relation::INNER_KEY => 'name',
                            Relation::OUTER_KEY => 'name',
                            Relation::THROUGH_INNER_KEY => 'child',
                            Relation::THROUGH_OUTER_KEY => 'parent',
                        ],
                    ],
                    'children' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => 'rbac_item',
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => 'rbac_item_inheritance',
                            Relation::INNER_KEY => 'name',
                            Relation::OUTER_KEY => 'name',
                            Relation::THROUGH_INNER_KEY => 'parent',
                            Relation::THROUGH_OUTER_KEY => 'child',
                        ],
                    ],
                ],
            ],
            RbacRole::class => [
                Schema::ROLE => RbacItemAbstract::class,
            ],
            RbacPermission::class => [
                Schema::ROLE => RbacItemAbstract::class,
            ],
            'rbac_item_inheritance' => [
                Schema::ROLE => 'rbac_item_inheritance',
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'rbac_item_inheritance',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'parent', 'child'],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testStore(): void
    {
        $role = new RbacRole('superAdmin');

        $permission = new RbacPermission('writeUser');

        $role->children->add($permission);
        $permission->parents->add($role);

        $tr = new Transaction($this->orm);
        $tr->persist($role);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), 'rbac_item');
        /** @var RbacRole $fetchedRole */
        $fetchedRole = $selector->wherePK('superAdmin')->fetchOne();

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

        $tr = new Transaction($this->orm);
        $tr->persist($role);
        $tr->run();

        unset($role, $permission);

        $this->orm = $this->orm->withHeap(new Heap());

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm, 'rbac_item'))->wherePK('superAdmin')->fetchOne();
        /** @var RbacPermission $fetchedPermission */
        $fetchedPermission = (new Select($this->orm, 'rbac_item'))->wherePK('writeUser')->fetchOne();

        $fetchedRole->children->removeElement($fetchedPermission);
        $fetchedPermission->parents->removeElement($fetchedRole);

        $tr = new Transaction($this->orm);
        $tr->persist($fetchedRole);
        $tr->run();

        $fetchedRole->children->add($fetchedPermission);
        $fetchedPermission->parents->add($fetchedRole);

        $tr = new Transaction($this->orm);
        $tr->persist($fetchedRole);
        $tr->run();

        self::assertTrue(true);
    }

    public function testNotTriggersRehydrate(): void
    {
        $role = new RbacRole('superAdmin', 'description');

        $permission = new RbacPermission('writeUser');

        $role->children->add($permission);
        $permission->parents->add($role);

        $tr = new Transaction($this->orm);
        $tr->persist($role);
        $tr->run();

        unset($role, $permission);

        $this->orm = $this->orm->withHeap(new Heap());

        /** @var RbacRole $fetchedRole */
        $fetchedRole = (new Select($this->orm, 'rbac_item'))->wherePK('superAdmin')->fetchOne();
        /** @var RbacPermission $fetchedPermission */
        $fetchedPermission = (new Select($this->orm, 'rbac_item'))->wherePK('writeUser')->fetchOne();

        $fetchedRole->description = 'updated description';

        // unlink
        $fetchedRole->children->removeElement($fetchedPermission);
        $fetchedPermission->parents->removeElement($fetchedRole);

        self::assertSame('updated description', $fetchedRole->description);

        $tr = new Transaction($this->orm);
        $tr->persist($fetchedRole);
        $tr->run();
    }
}
