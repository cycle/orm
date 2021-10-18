<?php
// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

class Identity
{
    private $id;
    private $key;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key): void
    {
        $this->key = $key;
    }
}
