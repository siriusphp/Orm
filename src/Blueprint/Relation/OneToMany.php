<?php
declare(strict_types=1);

namespace Sirius\Orm\Blueprint\Relation;

use Sirius\Orm\Blueprint\Mapper;
use Sirius\Orm\Blueprint\Relation;
use Sirius\Orm\Helpers\Inflector;
use Sirius\Orm\Relation\RelationConfig;

class OneToMany extends Relation
{
    protected $type = RelationConfig::TYPE_ONE_TO_MANY;

    protected $cascade;

    protected $aggregates = [];

    /**
     * @return bool
     */
    public function getCascade()
    {
        return $this->cascade;
    }

    /**
     * @param bool $cascade
     *
     * @return Relation
     */
    public function setCascade(bool $cascade)
    {
        $this->cascade = $cascade;

        return $this;
    }

    public function addAggregate($name, $aggregate)
    {
        $this->aggregates[$name] = $aggregate;

        return $this;
    }

    public function getAggregates(): array
    {
        return $this->aggregates;
    }

    public function setMapper(Mapper $mapper): Relation
    {
        if ($mapper && ! $this->foreignKey) {
            $this->foreignKey = Inflector::singularize($mapper->getName()) . '_id';
        }

        if ($mapper && ! $this->nativeKey) {
            $this->nativeKey = $mapper->getPrimaryKey();
        }

        return parent::setMapper($mapper);
    }
}
