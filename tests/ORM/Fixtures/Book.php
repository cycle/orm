<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use DateTimeImmutable;
use DateTimeInterface;

class Book
{
    public $id;

    /** @var BookStates */
    public $states;

    /** @var BookNestedStates */
    public $nested_states;

    /** @var DateTimeInterface */
    public $published_at;

    public function __construct(?DateTimeInterface $publishedAt = null)
    {
        $this->states = new BookStates();
        $this->nested_states = new BookNestedStates();
        $this->published_at = $publishedAt instanceof DateTimeInterface ? $publishedAt : new DateTimeImmutable();
    }
}
