<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation\Traits;

use Spiral\ORM\Mapper\PromiseFactoryInterface;
use Spiral\ORM\Node;
use Spiral\ORM\Util\Promise\PromiseOne;

trait PromiseOneTrait
{
    /**
     * @inheritdoc
     */
    public function initPromise(Node $parentNode): array
    {
        if (empty($innerKey = $this->fetchKey($parentNode, $this->innerKey))) {
            return [null, null];
        }

        $scope = [
            $this->outerKey => $innerKey
        ];

        if (!empty($e = $this->orm->get($this->target, $scope, false))) {
            return [$e, $e];
        }

        $mapper = $this->getMapper();
        if ($mapper instanceof PromiseFactoryInterface) {
            $p = $mapper->initProxy($scope);
        } else {
            $p = new PromiseOne($this->orm, $this->target, $scope);
        }

        return [$p, $p];
    }
}