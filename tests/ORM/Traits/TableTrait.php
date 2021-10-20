<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Traits;

use Cycle\ORM\Tests\Util\TableRenderer;
use Cycle\Database\Database;
use Cycle\Database\ForeignKeyInterface;

trait TableTrait
{
    public function makeTable(
        string $table,
        array $columns,
        array $fk = [],
        array $pk = null,
        array $defaults = []
    ): void {
        $schema = $this->getDatabase()->table($table)->getSchema();
        $renderer = new TableRenderer();
        $renderer->renderColumns($schema, $columns, $defaults);

        foreach ($fk as $column => $options) {
            $fkState = $schema->foreignKey([$column])->references($options['table'], [$options['column']]);
            $fkState->onUpdate($options['onUpdate'] ?? ForeignKeyInterface::CASCADE);
            $fkState->onDelete($options['onDelete'] ?? ForeignKeyInterface::CASCADE);
        }

        if (!empty($pk)) {
            $schema->setPrimaryKeys($pk);
        }

        $schema->save();
    }

    public function makeFK(
        string $from,
        string $fromKey,
        string $to,
        string $toColumn,
        string $onDelete = ForeignKeyInterface::CASCADE,
        string $onUpdate = ForeignKeyInterface::CASCADE
    ): void {
        $schema = $this->getDatabase()->table($from)->getSchema();
        $schema->foreignKey([$fromKey])->references($to, [$toColumn])->onDelete($onDelete)->onUpdate($onUpdate);
        $schema->save();
    }

    public function makeCompositeFK(
        string $from,
        array $fromKeys,
        string $to,
        array $toColumns,
        string $onDelete = ForeignKeyInterface::CASCADE,
        string $onUpdate = ForeignKeyInterface::CASCADE
    ): void {
        $schema = $this->getDatabase()->table($from)->getSchema();
        $schema->foreignKey($fromKeys)->references($to, $toColumns)->onDelete($onDelete)->onUpdate($onUpdate);
        $schema->save();
    }

    public function makeIndex(
        string $table,
        array $columns,
        bool $unique
    ): void {
        $schema = $this->getDatabase()->table($table)->getSchema();
        $schema->index($columns)->unique($unique);
        $schema->save();
    }

    abstract protected function getDatabase(): Database;
}
