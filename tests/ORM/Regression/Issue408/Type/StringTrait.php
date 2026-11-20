<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Regression\Issue408\Type;

trait StringTrait
{
    private function __construct(
        private string $id,
    ) {}

    public static function create(int|string $id): self
    {
        return new self((string) $id);
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
