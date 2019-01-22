<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

/**
 * Enum of possible relation options.
 */
final class Relation
{
    // General options
    public const TYPE   = 10;
    public const TARGET = 11;
    public const SCHEMA = 21;

    // Relation types (default)
    public const HAS_ONE           = 1;
    public const HAS_MANY          = 3;
    public const BELONGS_TO        = 2;
    public const REFERS_TO         = 4;
    public const MANY_TO_MANY      = 5;
    public const MANY_THOUGHT_MANY = 52;

    // Morphed relations
    public const BELONGS_TO_MORPHED = 99999;
    public const MORPHED_HAS_ONE    = 1000034;
    public const MORPHED_HAS_MANY   = 1000035;

    public const CASCADE   = 234214;
    public const NULLABLE  = 40000;
    public const OUTER_KEY = 2;
    public const INNER_KEY = 3;

    // Selections
    public const CONSTRAIN = 11111111;
    public const WHERE     = 910;

    // Many-To-Many relation(s) options
    public const THOUGHT_INNER_KEY = 908;
    public const THOUGHT_OUTER_KEY = 909;
    public const PIVOT_ENTITY      = 9880;
    public const PIVOT_TABLE       = 904;
    public const PIVOT_DATABASE    = 905;
    public const PIVOT_COLUMNS     = 9098;
    public const PIVOT_WHERE       = 911;

    // Custom morph key
    public const MORPH_KEY = 903;
}