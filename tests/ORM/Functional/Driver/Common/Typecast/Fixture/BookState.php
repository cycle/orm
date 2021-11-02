<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

class BookState
{
    public string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}
