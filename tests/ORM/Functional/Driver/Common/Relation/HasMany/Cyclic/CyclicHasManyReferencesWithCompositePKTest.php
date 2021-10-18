<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\Cyclic;

use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
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
                Schema::ROLE => 'tenant',
                Schema::MAPPER => TimestampedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tenants',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name', 'preference_id', 'created_at', 'updated_at'],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'preference_id' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
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
                Schema::ROLE => 'preference',
                Schema::MAPPER => TimestampedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'preferences',
                Schema::PRIMARY_KEY => ['id'],
                Schema::COLUMNS => ['tenant_id', 'id', 'flag', 'option', 'created_at', 'updated_at'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'tenant_id' => 'int',
                ],
                Schema::RELATIONS => [
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
                Schema::ROLE => 'documents',
                Schema::MAPPER => TimestampedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                //                Schema::PRIMARY_KEY => ['id'],
                Schema::PRIMARY_KEY => ['tenant_id', 'id'],
                Schema::COLUMNS => ['tenant_id', 'id', 'preference_id', 'body', 'created_at', 'updated_at'],
                Schema::SCHEMA => [],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'tenant_id' => 'int',
                    'preference_id' => 'int',
                ],
                Schema::RELATIONS => [
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

        $this->captureWriteQueries();
        $this->save($t);
        $this->assertNumWrites(3);

        // no changes!
        $this->captureWriteQueries();
        $this->save($t);
        $this->assertNumWrites(0);
    }
}
