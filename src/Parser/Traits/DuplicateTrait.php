<?php

declare(strict_types=1);

namespace Cycle\ORM\Parser\Traits;

/**
 * Trait provides ability for Node to ensure that given data is unique in selection. Primary key
 * would be used to tie duplicate nodes together.
 *
 * @internal
 */
trait DuplicateTrait
{
    /**
     * @var string[]
     *
     * @internal
     */
    protected array $duplicateCriteria = [];

    /** @internal */
    protected array $duplicates = [];

    /**
     * @param string[] $columns
     */
    protected function setDuplicateCriteria(array $columns): void
    {
        $this->duplicateCriteria = $columns;
    }

    /**
     * In many cases (for example if you have inload of HAS_MANY relation) record data can be
     * replicated by many result rows (duplicated). To ensure proper data links we have to
     * deduplicate such records. This method use reference based feedback loop.
     *
     * @param array $data Reference to parsed record data, reference will be pointed to valid and
     *                    existed data segment if such data was already parsed.
     *
     * @return bool Return true if data is unique.
     */
    final protected function deduplicate(array &$data): bool
    {
        if ($this->duplicateCriteria === []) {
            return true;
        }

        return \count($this->duplicateCriteria) === 1
            ? $this->deduplicateSingle(\current($this->duplicateCriteria), $data)
            : $this->deduplicateMultiple($this->duplicateCriteria, $data);
    }

    private function deduplicateMultiple(array $keys, array &$data): bool
    {
        $zoom = &$this->duplicates;
        $search = true;
        $count = \count($keys);
        foreach ($keys as $key) {
            --$count;
            $criteria = (string) $data[$key];

            if (!$search || !\array_key_exists($criteria, $zoom)) {
                $search = false;
                if ($count === 0) {
                    $zoom[$criteria] = &$data;
                    return true;
                }
                $zoom[$criteria] = [];
            }
            $zoom = &$zoom[$criteria];
        }
        // duplicate is presented, let's reduplicate
        $data = $zoom;

        return false;
    }

    private function deduplicateSingle(string $key, array &$data): bool
    {
        $criteria = (string) $data[$key];

        if (isset($this->duplicates[$criteria])) {
            // duplicate is presented, let's reduplicate
            $data = $this->duplicates[$criteria];

            return false;
        }

        // remember record to prevent future duplicates
        $this->duplicates[$criteria] = &$data;

        return true;
    }
}
