<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

final class BookNestedStates
{
    /** @var BookState[] */
    public $states;

    public function __construct(array $states = [])
    {
        $this->states = $this->create($states);
    }

    public function __toString(): string
    {
        return implode('|', array_column($this->states, 'title'));
    }

    public static function cast(string $value): self
    {
        return new self(explode('|', $value));
    }

    private function create(array $states)
    {
        return array_map(static function (string $state) {
            return new BookState($state);
        }, $states);
    }
}
