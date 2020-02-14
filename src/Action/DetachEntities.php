<?php
declare(strict_types=1);

namespace Sirius\Orm\Action;

use Sirius\Orm\Entity\EntityInterface;
use Sirius\Orm\Relation\Relation;

class DetachEntities extends AttachEntities
{
    public function revert()
    {
        /**
         * @todo restore previous values
         */
    }

    public function run()
    {
        /**
         * @todo store current attribute values
         */
    }

    public function onSuccess()
    {
        $this->relation->detachEntities($this->nativeEntity, $this->foreignEntity);
    }
}
