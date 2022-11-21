<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Employee;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\Manager;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\Fixture\ManagerWithCredentials;
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\JtiBaseTest;

abstract class EmbeddedRelationsTest extends JtiBaseTest
{
    protected const EMPLOYEE_ROLE = 'employee';
    protected const MANAGER_ROLE = 'manager_with_credentials';

    protected const EMPLOYEE_1 = ['id' => 1, 'name' => 'John', 'age' => 38];
    protected const EMPLOYEE_2 = ['id' => 2, 'name' => 'Anton', 'age' => 35];
    protected const EMPLOYEE_3 = ['id' => 3, 'name' => 'Kentarius', 'age' => 27];
    protected const EMPLOYEE_4 = ['id' => 4, 'name' => 'Valeriy', 'age' => 32];
    protected const MANAGER_1 = ['id' => 1, 'rank' => 'top'];
    protected const MANAGER_3 = ['id' => 3, 'rank' => 'bottom'];

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('employee', [
            'id' => 'integer',
            'name' => 'string',
            'age' => 'integer,nullable',
        ], pk: ['id']);

        $this->makeTable('manager_with_credentials', [
            'id' => 'integer',
            'rank' => 'string',
            'creds_username' => 'string,nullable',
            'creds_password' => 'string,nullable',
        ], fk: [
            'id' => ['table' => 'employee', 'column' => 'id'],
        ], pk: ['id']);

        $this->getDatabase()->table('employee')->insertMultiple(
            array_keys(static::EMPLOYEE_1),
            [
                self::EMPLOYEE_1,
                self::EMPLOYEE_2,
                self::EMPLOYEE_3,
                self::EMPLOYEE_4,
            ]
        );
        $this->getDatabase()->table('manager_with_credentials')->insertMultiple(
            array_keys(static::MANAGER_1),
            [
                self::MANAGER_1,
                self::MANAGER_3,
            ]
        );
    }

    /**
     * Parent's relation should be initialized
     */
    public function testInsertToEmbeddedFieldsForJtiChildEntity(): void
    {
        /** @var ManagerWithCredentials $entity */
        $entity = (new Select($this->orm, self::MANAGER_ROLE))
            ->wherePK(1)
            ->fetchOne();

        $entity->credentials->username = 'roquie';
        $entity->credentials->password = 'secret';

        $this->captureWriteQueries();
        $this->save($entity);
        $this->assertNumWrites(1);

        $entity = (new Select($this->orm, self::MANAGER_ROLE))
            ->wherePK(1)
            ->fetchOne();

        $this->assertSame('roquie', $entity->credentials->username);
        $this->assertSame('secret', $entity->credentials->password);
    }

    /**
     * Parent's relation should be initialized
     */
    public function testInsertToCreatingNewEmbeddedObjectForJtiChildEntity(): void
    {
        $this->markTestSkipped(
            'TODO: Must be fixed. When we create new embedded object, we should update entity\'s node in heap'
        );

        /** @var ManagerWithCredentials $entity */
        $entity = (new Select($this->orm, self::MANAGER_ROLE))
            ->wherePK(1)
            ->fetchOne();

        $credentials = new UserCredentials();
        $credentials->username = 'roquie';
        $credentials->password = 'secret';

        $entity->credentials = $credentials;

        $this->captureWriteQueries();
        $this->save($entity);
        $this->assertNumWrites(1);

        $entity = (new Select($this->orm, self::MANAGER_ROLE))
            ->wherePK(1)
            ->fetchOne();


        $this->assertSame('roquie', $entity->credentials->username);
        $this->assertSame('secret', $entity->credentials->password);
    }

    protected function getSchemaArray(): array
    {
        return [
            static::EMPLOYEE_ROLE => [
                SchemaInterface::ENTITY => Employee::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'employee',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'name', 'age'],
                SchemaInterface::TYPECAST => ['id' => 'int', 'age' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
            static::MANAGER_ROLE => [
                SchemaInterface::ENTITY => Manager::class,
                SchemaInterface::MAPPER => static::DEFAULT_MAPPER,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'manager_with_credentials',
                SchemaInterface::PARENT => static::EMPLOYEE_ROLE,
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'rank'],
                SchemaInterface::TYPECAST => ['id' => 'int'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [
                    'credentials' => [
                        Relation::TYPE => Relation::EMBEDDED,
                        Relation::TARGET => 'user:credentials',
                        Relation::LOAD => Relation::LOAD_EAGER,
                        Relation::SCHEMA => [],
                    ],
                ],
            ],
            UserCredentials::class => [
                SchemaInterface::ROLE => 'user:credentials',
                SchemaInterface::ENTITY => UserCredentials::class,
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'manager_with_credentials',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'username' => 'creds_username',
                    'password' => 'creds_password',
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::TYPECAST => ['id' => 'int'],
                SchemaInterface::RELATIONS => [],
            ],
        ];
    }
}
