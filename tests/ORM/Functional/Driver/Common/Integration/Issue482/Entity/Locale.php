<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\Entity;

class Locale
{
    public ?int $id = null;
    public string $code;

    /** @var iterable<Translation> */
    public iterable $translations = [];

    public function __construct(string $code)
    {
        $this->code = $code;
    }
}
