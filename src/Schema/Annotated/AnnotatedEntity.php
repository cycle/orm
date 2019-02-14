<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Annotated;

class AnnotatedEntity
{
    /** @var string */
    private $class;

    /** @var string */
    private $role;

    /**
     * @param string      $class
     * @param string|null $role
     */
    public function __construct(string $class, string $role = null)
    {
        $this->class = $class;
        $this->role = $role;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }
}