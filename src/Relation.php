<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

class Relation
{
    public const HAS_ONE    = 1;
    public const HAS_MANY   = 3;
    public const BELONGS_TO = 2;
    public const REFERS_TO  = 4;

    public const OUTER_KEY = 2;
    public const INNER_KEY = 3;
    public const NULLABLE  = 4;

    public const TYPE   = 10;
    public const TARGET = 11;
    public const SCHEMA = 21;

    public const ORDER_BY = 1111;

    public const CASCADE = 234214;
}