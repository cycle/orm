<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class JtiBaseTest extends BaseTest
{
    use TableTrait;

    protected const DEFAULT_MAPPER = Mapper::class;

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
    }
}
