<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

class Relation
{
    public const HAS_ONE            = 1;
    public const HAS_MANY           = 3;
    public const BELONGS_TO         = 2;
    public const REFERS_TO          = 4;
    public const MANY_TO_MANY       = 5;
    public const MANY_THOUGHT_MANY  = 52;
    public const OUTER_KEY          = 2;
    public const INNER_KEY          = 3;
    public const NULLABLE           = 40000;
    public const TYPE               = 10;
    public const TARGET             = 11;
    public const SCHEMA             = 21;
    public const CONSTRAIN          = 11111111;
    public const CASCADE            = 234214;
    public const MORPH_KEY          = 903;
    public const PIVOT_ENTITY       = 9880;
    public const PIVOT_TABLE        = 904;
    public const PIVOT_DATABASE     = 905;
    public const PIVOT_COLUMNS      = 906;
    public const PIVOT_DEFAULTS     = 907;
    public const PIVOT_WHERE        = 911;
    public const THOUGHT_INNER_KEY  = 908;
    public const THOUGHT_OUTER_KEY  = 909;
    public const WHERE              = 910;
    public const BELONGS_TO_MORPHED = 99999;
    public const MORPHED_HAS_ONE    = 1000034;
    public const MORPHED_HAS_MANY   = 1000035;
}