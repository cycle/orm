<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\CaseTemplate\Entity;

use DateTimeImmutable;

class Tag
{
    public ?int $id = null;
    public string $label;
    public DateTimeImmutable $created_at;
    /** @var Post[] */
    public array $posts = [];

    public function __construct(string $label)
    {
        $this->label = $label;
        $this->created_at = new DateTimeImmutable();
    }
}
