<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class BookState
{
    public $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}
