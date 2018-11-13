<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures;

class Cyclic
{
    public $name;

    /** @var Cyclic|null */
    public $cyclic;
}