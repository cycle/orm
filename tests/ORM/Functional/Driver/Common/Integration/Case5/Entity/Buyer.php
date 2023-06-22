<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case5\Entity;

class Buyer extends User
{
    public function __construct(
        int $id,
        string $name,
        public string $address,
        public array $partners = []
    ) {
        $this->id = $id;
        $this->name = $name;
    }
}
