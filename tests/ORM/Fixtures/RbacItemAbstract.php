<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;

class RbacItemAbstract
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var DoctrineCollection|RbacRole[]|RbacPermission[]
     * @phpstan-var DoctrineCollection<string,RbacRole|RbacPermission>
     */
    public $parents;

    /**
     * @var DoctrineCollection|RbacRole[]|RbacPermission[]
     * @phpstan-var DoctrineCollection<string,RbacRole|RbacPermission>
     */
    public $children;

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->parents = new ArrayCollection();
        $this->children = new ArrayCollection();
    }
}
