<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Promise\ReferenceInterface;

class UserID implements ReferenceInterface
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function __role(): string
    {
        return 'user';
    }

    public function __scope(): array
    {
        return ['id' => $this->id];
    }
}