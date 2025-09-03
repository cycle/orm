<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue528\Entity;

class Country
{
    public ?int $id = null;
    public string $name;

    /** @var iterable<Translation> */
    public iterable $translations = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
