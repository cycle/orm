<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue408\Type;

trait IntegerTrait
{
    private function __construct(
        private int $id,
    ) {}

    public static function create(int|string $id): self
    {
        return new self((int) $id);
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
