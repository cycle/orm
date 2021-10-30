<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Typecast\Fixture;

use DateTimeImmutable;
use DateTimeInterface;

class Book
{
    public $id;

    public BookStates $states;

    public BookNestedStates $nested_states;

    public DateTimeInterface $published_at;

    public function __construct(?DateTimeInterface $publishedAt = null)
    {
        $this->states = new BookStates();
        $this->nested_states = new BookNestedStates();
        $this->published_at = $publishedAt instanceof DateTimeInterface ? $publishedAt : new DateTimeImmutable();
    }
}
