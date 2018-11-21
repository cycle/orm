<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\State;

trait CollectionTrait
{
    /**
     * Init relation state and entity collection.
     *
     * @param array $data
     * @return array
     */
    public function init($data): array
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = $this->getORM()->make($this->class, $item, State::LOADED);
        }

        return [new ArrayCollection($result), $result];
    }

    /**
     * Convert entity data into array.
     *
     * @param mixed $data
     * @return array
     */
    public function extract($data)
    {
        if ($data instanceof Collection) {
            return $data->toArray();
        }

        return is_array($data) ? $data : [];
    }

    abstract protected function getORM(): ORMInterface;
}