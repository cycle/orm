<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures\HasOne;

class Profile
{
    public $id;
    public $image;

    /**
     * @var Nested
     */
    public $nested;
}