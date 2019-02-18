<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema;

use Spiral\Cycle\Select\SourceInterface;
use Spiral\Database\DatabaseManager;

interface EntityInterface
{
    /**
     * @return string
     */
    public function getRole(): string;

    /**
     * @param DatabaseManager $manager
     * @return SourceInterface
     */
    public function getSource(DatabaseManager $manager): SourceInterface;

    /**
     * @return array
     */
    public function getFields(): array;
}