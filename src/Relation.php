<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

/**
 * Enum of possible relation options.
 */
final class Relation
{
    // General options
    public const TYPE   = 0;
    public const TARGET = 1;
    public const SCHEMA = 2;

    // Relation types (default)
    public const HAS_ONE      = 10;
    public const HAS_MANY     = 11;
    public const BELONGS_TO   = 12;
    public const REFERS_TO    = 13;
    public const MANY_TO_MANY = 14;

    // Morphed relations
    public const BELONGS_TO_MORPHED = 20;
    public const MORPHED_HAS_ONE    = 21;
    public const MORPHED_HAS_MANY   = 23;

    // Custom morph key
    public const MORPH_KEY = 29;

    // Common relation options
    public const CASCADE   = 30;
    public const NULLABLE  = 31;
    public const OUTER_KEY = 32;
    public const INNER_KEY = 33;

    // Selections
    public const CONSTRAIN = 40;
    public const WHERE     = 41;

    // Many-To-Many relation(s) options
    public const THOUGHT_INNER_KEY = 50;
    public const THOUGHT_OUTER_KEY = 51;
    public const THOUGHT_ENTITY    = 52;
    public const THOUGHT_CONSTRAIN = 53;
    public const THOUGHT_WHERE     = 54;
}