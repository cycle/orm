<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity;

class Country
{
    public ?int $id = null;
    public string $name;
    public string $code;
    public bool $isFriendly = true;

    /** @var iterable<Translation> */
    public iterable $translations = [];

    public function __construct(string $name, string $code)
    {
        $this->name = $name;
        $this->code = $code;
    }
}
