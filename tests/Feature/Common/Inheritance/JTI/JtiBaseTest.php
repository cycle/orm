<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Inheritance\JTI;

use Cycle\ORM\Collection\ArrayCollectionFactory;
use Cycle\ORM\Config\RelationConfig;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Lifecycle\BeforeTest;

abstract class JtiBaseTest extends OrmTestCase
{
    protected const DEFAULT_MAPPER = Mapper::class;

    #[BeforeTest]
    public function setUp(): void
    {
        $factory = new Factory(
            $this->dbal,
            RelationConfig::getDefault(),
            null,
            new ArrayCollectionFactory(),
        );
        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()))->withFactory($factory);
    }

    abstract protected function getSchemaArray(): array;
}
