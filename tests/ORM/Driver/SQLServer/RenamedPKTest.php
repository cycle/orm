<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\SQLServer;

class RenamedPKTest extends \Cycle\ORM\Tests\RenamedPKTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getDatabase()->execute('SET IDENTITY_INSERT simple_entity ON');
    }

    public const DRIVER = 'sqlserver';
}
