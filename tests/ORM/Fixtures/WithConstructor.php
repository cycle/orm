<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class WithConstructor
{
    public $id;
    public $email;

    /** @var Post[]|Collection */
    public $comments;

    public $called;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->comments = new ArrayCollection();
        $this->called = true;
    }

    public function getID()
    {
        return $this->id;
    }
}
