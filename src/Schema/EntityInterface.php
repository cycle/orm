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
use Spiral\Database\Schema\AbstractTable;

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
     * Render associate table schema.
     *
     * @param AbstractTable $table
     */
    public function render(AbstractTable $table);

    /**
     * Associate entity with specific table schema. Entity must fetch all known
     * and used columns at this step.
     *
     * @param AbstractTable $table
     */
    public function associate(AbstractTable $table);

    /**
     * Return list of all relations associated with given entity.
     *
     * @return RelationInterface[]
     */
    public function getRelations(): array;

    /**
     * Pack entity schema into internal format.
     *
     * @return array
     */
    public function packSchema(): array;
}