<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

final class FinalEntity
{
    private(set) ?int $id = null;

    public function __construct(
        private(set) readonly string $uuid = '',
        private(set) string $title = '',
        public ?string $slug = null,
    ) {}
}
