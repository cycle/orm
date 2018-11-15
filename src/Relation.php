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
    public const HAS_ONE      = 1;
    public const HAS_MANY     = 3;
    public const BELONGS_TO   = 2;
    public const REFERS_TO    = 4;
    public const MANY_TO_MANY = 5;
    public const OUTER_KEY    = 2;
    public const INNER_KEY    = 3;
    public const NULLABLE     = 4;
    public const TYPE         = 10;
    public const TARGET       = 11;
    public const SCHEMA       = 21;
    public const ORDER_BY     = 1111;
    public const CASCADE      = 234214;

    const MORPH_KEY         = 903; //Morph key name (internal)
    const PIVOT_TABLE       = 904; //Pivot table name
    const PIVOT_DATABASE    = 905; //Pivot database (internal)
    const PIVOT_COLUMNS     = 906; //Pre-defined pivot table columns
    const PIVOT_DEFAULTS    = 907; //Pre-defined pivot table default values
    const THOUGHT_INNER_KEY = 908; //Pivot table options
    const THOUGHT_OUTER_KEY = 909; //Pivot table options
    const WHERE             = 910; //Where conditions
    const WHERE_PIVOT       = 911; //Where pivot conditions
}