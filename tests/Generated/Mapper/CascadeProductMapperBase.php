<?php

declare(strict_types=1);

namespace Sirius\Orm\Tests\Generated\Mapper;

use Sirius\Orm\Action\Delete as DeleteAction;
use Sirius\Orm\Action\Insert as InsertAction;
use Sirius\Orm\Action\SoftDelete as SoftDeleteAction;
use Sirius\Orm\Action\Update as UpdateAction;
use Sirius\Orm\Behaviour\Timestamps;
use Sirius\Orm\Connection;
use Sirius\Orm\Entity\GenericHydrator;
use Sirius\Orm\Entity\StateEnum;
use Sirius\Orm\Exception\FailedActionException;
use Sirius\Orm\Mapper;
use Sirius\Orm\MapperConfig;
use Sirius\Orm\Tests\Generated\Entity\CascadeProduct;
use Sirius\Sql\Bindings;

/**
 * @method CascadeProductQuery where($column, $value, $condition)
 * @method CascadeProductQuery orderBy(string $expr, string ...$exprs)
 */
abstract class CascadeProductMapperBase extends Mapper
{
    protected $createdAtColumn = 'created_on';
    protected $updatedAtColumn = 'updated_on';
    protected $deletedAtColumn = 'deleted_on';

    protected function init()
    {
        $this->mapperConfig = MapperConfig::fromArray([
            'entityClass' => 'Sirius\Orm\Tests\Generated\Entity\CascadeProduct',
            'primaryKey' => 'id',
            'table' => 'tbl_products',
            'tableAlias' => 'products',
            'guards' => [],
            'columns' => ['id', 'sku', 'price', 'attributes', 'created_on', 'updated_on', 'deleted_on'],
            'columnAttributeMap' => ['price' => 'value'],
            'casts' => [
                'id' => 'int',
                'sku' => 'string',
                'price' => 'decimal:2',
                'attributes' => 'array',
                'created_on' => 'DateTime',
                'updated_on' => 'DateTime',
                'deleted_on' => 'DateTime',
            ],
        ]);
        $this->hydrator     = new GenericHydrator($this->orm->getCastingManager());
        $this->hydrator->setMapper($this);

        $this->initRelations();
        $this->behaviours->add(new Timestamps($this->createdAtColumn, $this->updatedAtColumn));
    }

    protected function initRelations()
    {
        $this->addRelation('images', [
            'type' => 'one_to_many',
            'cascade' => true,
            'native_key' => 'id',
            'foreign_mapper' => 'images',
            'foreign_key' => 'content_id',
            'foreign_guards' => ['content_type' => 'products'],
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('ebay', [
            'type' => 'one_to_one',
            'cascade' => true,
            'native_key' => 'id',
            'foreign_mapper' => 'ebay_products',
            'foreign_key' => 'product_id',
            'load_strategy' => 'lazy',
        ]);
    }

    public function find($pk, array $load = []): ?CascadeProduct
    {
        return $this->newQuery()->find($pk, $load);
    }

    public function newQuery(): CascadeProductQuery
    {
        $query = new CascadeProductQuery($this->getReadConnection(), $this);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function newSubselectQuery(Connection $connection, Bindings $bindings, string $indent): CascadeProductQuery
    {
        $query = new CascadeProductQuery($this->getReadConnection(), $this, $bindings, $indent);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function save(CascadeProduct $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'saving', $entity);
        $action = $this->newSaveAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'saved', $entity);

        return $result;
    }

    public function newSaveAction(CascadeProduct $entity, $options): UpdateAction
    {
        if ( ! $this->getHydrator()->getPk($entity) || $entity->getState() == StateEnum::NEW) {
            $action = new InsertAction($this, $entity, $options);
        } else {
            $action = new UpdateAction($this, $entity, $options);
        }

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }

    public function delete(CascadeProduct $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'deleting', $entity);
        $action = $this->newDeleteAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'deleted', $entity);

        return $result;
    }

    public function newDeleteAction(CascadeProduct $entity, $options)
    {
        $options = array_merge((array) $options, ['deleted_at_column' => $this->deletedAtColumn]);
        $action = new SoftDeleteAction($this, $entity, $options);

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }

    public function forceDelete(CascadeProduct $entity, $withRelations = false)
    {
        $entity = $this->behaviours->apply($this, 'deleting', $entity);
        $action = new DeleteAction($this, $entity, ['relations' => $withRelations]);

        return $this->runActionInTransaction($action);
    }

    public function restore($pk): bool
    {
        $entity = $this->newQuery()
                       ->withTrashed()
                       ->find($pk);

        if ( ! $entity) {
            return false;
        }

        $this->getHydrator()->set($entity, $this->deletedAtColumn, null);
        $action = new UpdateAction($this, $entity);

        return $this->runActionInTransaction($action);
    }
}
