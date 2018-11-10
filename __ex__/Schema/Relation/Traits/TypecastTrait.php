<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema\Relation\Traits;

use Spiral\Database\ColumnInterface;

trait TypecastTrait
{
    /**
     * Provides ability to resolve type for FK column to be matching external column type.
     *
     * @param ColumnInterface $column
     * @return string
     */
    protected function resolveType(ColumnInterface $column): string
    {
        switch ($column->getAbstractType()) {
            case 'bigPrimary':
                return 'bigInteger';
            case 'primary':
                return 'integer';
            default:
                //Not primary key
                return $column->getAbstractType();
        }
    }
}