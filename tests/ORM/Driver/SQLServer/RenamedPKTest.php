<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Driver\SQLServer;

class RenamedPKTest extends \Cycle\ORM\Tests\RenamedPKTest
{
    public const DRIVER = 'sqlserver';

    public function testCreateWithPredefinedId(): void
    {
        $this->markTestSkipped(
            'Cannot insert explicit value for autoincrement column in the table when IDENTITY_INSERT is set to OFF.'
            . PHP_EOL . 'Should be fixed in https://github.com/cycle/orm/issues/167'
        );
    }
}
