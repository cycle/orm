<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\JTI;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class JtiBaseTest extends BaseTest
{
    use TableTrait;

    protected const
        EMPLOYEE_1 = ['id' => 1, 'name' => 'John', 'age' => 38],
        EMPLOYEE_2 = ['id' => 2, 'name' => 'Anton', 'age' => 35],
        EMPLOYEE_3 = ['id' => 3, 'name' => 'Kentarius', 'age' => 27],
        EMPLOYEE_4 = ['id' => 4, 'name' => 'Valeriy', 'age' => 32],

        ENGINEER_2 = ['id' => 2, 'level' => 8],
        ENGINEER_4 = ['id' => 4, 'level' => 10],

        PROGRAMATOR_2 = ['id' => 2, 'language' => 'php'],
        PROGRAMATOR_4 = ['id' => 4, 'language' => 'go'],

        MANAGER_1 = ['id' => 1, 'rank' => 'top'],
        MANAGER_3 = ['id' => 3, 'rank' => 'bottom'],

        EMPLOYEE_1_LOADED = self::EMPLOYEE_1,
        EMPLOYEE_2_LOADED = self::EMPLOYEE_2,
        EMPLOYEE_3_LOADED = self::EMPLOYEE_3,
        EMPLOYEE_4_LOADED = self::EMPLOYEE_4,

        ENGINEER_2_LOADED = self::ENGINEER_2 + self::EMPLOYEE_2_LOADED,
        ENGINEER_4_LOADED = self::ENGINEER_4 + self::EMPLOYEE_4_LOADED,

        PROGRAMATOR_2_LOADED = self::PROGRAMATOR_2 + self::ENGINEER_2_LOADED,
        PROGRAMATOR_4_LOADED = self::PROGRAMATOR_4 + self::ENGINEER_4_LOADED,

        MANAGER_1_LOADED = self::MANAGER_1 + self::EMPLOYEE_1_LOADED,
        MANAGER_3_LOADED = self::MANAGER_3 + self::EMPLOYEE_3_LOADED,

        EMPLOYEE_ALL_LOADED = [self::EMPLOYEE_1_LOADED, self::EMPLOYEE_2_LOADED, self::EMPLOYEE_3_LOADED, self::EMPLOYEE_4_LOADED],
        ENGINEER_ALL_LOADED = [self::ENGINEER_2_LOADED, self::ENGINEER_4_LOADED],
        PROGRAMATOR_ALL_LOADED = [self::PROGRAMATOR_2_LOADED, self::PROGRAMATOR_4_LOADED],
        MANAGER_ALL_LOADED = [self::MANAGER_1_LOADED, self::MANAGER_3_LOADED];

    abstract protected function getSchemaArray(): array;

    public function setUp(): void
    {
        parent::setUp();

        $factory = new Factory(
            $this->dbal,
            RelationConfig::getDefault(),
            null,
            new ArrayCollectionFactory()
        );
        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()))->withFactory($factory);
        $this->logger->display();
    }
}
