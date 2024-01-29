<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case416\Entity;

class UserName
{
    public const ROLE = 'UserName';

    public const F_FIRST_NAME = 'firstName';
    public const F_LAST_NAME = 'lastName';

    public function __construct(
        public string $firstName = '',
        public string $lastName = '',
    ) {
    }
}
