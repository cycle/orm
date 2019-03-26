<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise;

class Reference implements ReferenceInterface
{
    /** @var string */
    private $role;

    /** @var array */
    private $scope = [];

    /**
     * @param string $role
     * @param array  $scope
     */
    public function __construct(string $role, array $scope)
    {
        $this->role = $role;
        $this->scope = $scope;
    }

    /**
     * @inheritdoc
     */
    public function __role(): string
    {
        return $this->role;
    }

    /**
     * @inheritdoc
     */
    public function __scope(): array
    {
        return $this->scope;
    }
}