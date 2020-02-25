<?php
declare(strict_types=1);

namespace Sirius\Orm\Entity;

use Sirius\Orm\Relation\Relation;

class LazyRelation implements LazyLoader
{
    /**
     * @var EntityInterface
     */
    protected $entity;
    /**
     * @var Tracker
     */
    protected $tracker;
    /**
     * @var Relation
     */
    protected $relation;

    public function __construct(EntityInterface $entity, Tracker $tracker, Relation $relation)
    {
        $this->entity   = $entity;
        $this->tracker  = $tracker;
        $this->relation = $relation;
    }

    public function load()
    {
        $results = $this->tracker->getResultsForRelation($this->relation->getOption('name'));
        $this->relation->attachMatchesToEntity($this->entity, $results);
    }
}
