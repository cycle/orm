<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class AsymmetricVisibilityEntity
{
    public ?int $id = null;
    public string $name = '';
    private(set) string $login = '';
    protected(set) string $email = '';
    private string $password = '';
}
