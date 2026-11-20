<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Traits\ErrorTrait;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ErrorTraitTest
{
    public function errorNamesAwaitedScopeAndContext(): void
    {
        $command = new class {
            use ErrorTrait;

            protected array $waitScope = ['id' => true];
            protected array $waitContext = ['user_id' => true, 'tenant_id' => true];
        };

        Assert::same($command->__toError(), $command::class . '(scope:id, user_id, tenant_id)');
    }
}
