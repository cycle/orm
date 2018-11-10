<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database\Traits;

trait PrimaryKeyTrait
{
    /**
     * Primary key value (from previous command), promised on execution!.
     *
     * @var mixed
     */
    private $primaryKey;

    /**
     * Promised on execute.
     *
     * @return mixed|null
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param mixed $primaryKey
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }
}