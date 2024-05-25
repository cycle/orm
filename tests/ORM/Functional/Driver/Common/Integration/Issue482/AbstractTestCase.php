<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482;

use Cycle\Database\Injection\Fragment;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\IntegrationTestTrait;
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity\Country;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class AbstractTestCase extends BaseTest
{
    use IntegrationTestTrait;
    use TableTrait;

    public function setUp(): void
    {
        // Init DB
        parent::setUp();
        $this->makeTables();
        $this->fillData();

        $this->loadSchema(__DIR__ . '/schema.php');
    }

    public function testSelect(): void
    {
        $this->logger->display();

        $result = $this->orm->getRepository(Country::class)
            ->select()
            ->where('is_friendly', true)
            // User wants to search everywhere
            ->with('translations', [
                'as' => 'trans',
                'method' => JoinableLoader::LEFT_JOIN,
                'alias' => 'trans1',
            ])
            // User wants to search everywhere
            ->where(function (QueryBuilder $qb): void {
                $searchProperties = ['code', 'name', 'trans.title'];
                foreach ($searchProperties as $propertyName) {
                    $qb->orWhere($propertyName, 'LIKE', "%ищу тебя%");
                }
            })
            // User want to sort by translation
            ->with('translations', [
                'as' => 'transRu',
                'method' => JoinableLoader::LEFT_JOIN,
                'where' => [
                    'locale_id' => 1,
                ],
                'alias' => 'trans2',
            ])
            ->orderBy(new Fragment('ISNULL(transRu.title)'))
            ->orderBy('transRu.title', 'asc')
            ->fetchAll();



        self::assertEquals([

        ],$result);
    }

    private function makeTables(): void
    {
        // Make tables
        $this->makeTable('country', [
            'id' => 'primary', // autoincrement
            'name' => 'string',
            'code' => 'string',
            'is_friendly' => 'bool',
        ]);

        $this->makeTable('locale', [
            'id' => 'primary',
            'code' => 'string',
        ]);

        $this->makeTable('translation', [
            'id' => 'primary',
            'title' => 'string',
            'country_id' => 'int',
            'locale_id' => 'int',
        ]);
        $this->makeFK('translation', 'country_id', 'country', 'id', 'NO ACTION', 'NO ACTION');
        $this->makeFK('translation', 'locale_id', 'locale', 'id', 'NO ACTION', 'NO ACTION');
    }

    private function fillData(): void
    {
        $this->getDatabase()->table('locale')->insertMultiple(
            ['code'],
            [
                ['ru'],
                ['en'],
            ],
        );
        $this->getDatabase()->table('country')->insertMultiple(
            ['name', 'code', 'is_friendly'],
            [
                ['Russia', 'RUS', true],
                ['USA', 'USA', true],
                ['China', 'CHN', true],
            ],
        );
        $this->getDatabase()->table('translation')->insertMultiple(
            ['country_id', 'locale_id', 'title'],
            [
                [1, 1, 'Россия'],
                [1, 2, 'Америка'],
                [2, 1, 'Russia'],
                [2, 2, 'America'],
            ],
        );
    }
}
