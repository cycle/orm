<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\CyclicRef\TimestampedMapper;
use Cycle\ORM\Tests\Fixtures\CyclicRef2\Tenant;
use Cycle\ORM\Tests\Fixtures\CyclicRef2\Preference;
use Cycle\ORM\Tests\Fixtures\CyclicRef2\Document;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class CyclicHasManyReferencesWithCompositePKTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('tenants', [
            'id' => 'primary',
            'name' => 'string',
            'preference_id' => 'integer,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('preferences', [
            'tenant_id' => 'integer',
            'id' => 'primary',
            'flag' => 'boolean',
            'option' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('documents', [
            'tenant_id' => 'integer',
            'id' => 'bigPrimary',
            'preference_id' => 'integer',
            'body' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->orm = $this->withSchema(new Schema([
            Tenant::class => [
                SchemaInterface::ROLE => 'tenant',
                SchemaInterface::MAPPER => TimestampedMapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'tenants',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'name', 'preference_id', 'created_at', 'updated_at'],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'preference_id' => 'int',
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [
                    'preference' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Preference::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            //                            Relation::INNER_KEY => 'preference_id',
                            //                            Relation::OUTER_KEY => 'id',
                            Relation::INNER_KEY => ['id', 'preference_id'],
                            Relation::OUTER_KEY => ['tenant_id', 'id'],
                            Relation::NULLABLE => true,
                        ],
                    ],
                    'preferences' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Preference::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'tenant_id',
                        ],
                    ],
                ],
            ],
            Preference::class => [
                SchemaInterface::ROLE => 'preference',
                SchemaInterface::MAPPER => TimestampedMapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'preferences',
                SchemaInterface::PRIMARY_KEY => ['id'],
                SchemaInterface::COLUMNS => ['tenant_id', 'id', 'flag', 'option', 'created_at', 'updated_at'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'tenant_id' => 'int',
                ],
                SchemaInterface::RELATIONS => [
                    'tenant' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => Tenant::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'tenant_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => false,
                        ],
                    ],
                ],
            ],
            Document::class => [
                SchemaInterface::ROLE => 'documents',
                SchemaInterface::MAPPER => TimestampedMapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'post',
                //                Schema::PRIMARY_KEY => ['id'],
                SchemaInterface::PRIMARY_KEY => ['tenant_id', 'id'],
                SchemaInterface::COLUMNS => ['tenant_id', 'id', 'preference_id', 'body', 'created_at', 'updated_at'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'tenant_id' => 'int',
                    'preference_id' => 'int',
                ],
                SchemaInterface::RELATIONS => [
                    'tenant' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => Tenant::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'tenant_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'preference' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => Preference::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => ['tenant_id', 'preference_id'],
                            Relation::OUTER_KEY => ['tenant_id', 'id'],
                            //                            Relation::INNER_KEY => 'preference_id',
                            //                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testCreate(): void
    {
        $t = new Tenant();
        $t->name = 'Google';

        $p = new Preference();
        $p->flag = true;
        $p->option = 'option1';

        $t->setPreference($p);
        $p->tenant = $t;

        $this->captureWriteQueries();
        $this->save($t);
        $this->assertNumWrites(3);

        $this->assertNotNull($p->tenant_id);

        // no changes!
        $this->captureWriteQueries();
        $this->save($t);
        $this->assertNumWrites(0);
    }
}
