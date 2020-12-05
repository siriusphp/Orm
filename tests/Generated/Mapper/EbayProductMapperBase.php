<?php

declare(strict_types=1);

namespace Sirius\Orm\Tests\Generated\Mapper;

use Sirius\Orm\Action\Delete as DeleteAction;
use Sirius\Orm\Action\Insert as InsertAction;
use Sirius\Orm\Action\Update as UpdateAction;
use Sirius\Orm\Connection;
use Sirius\Orm\Entity\ClassMethodsHydrator;
use Sirius\Orm\Entity\StateEnum;
use Sirius\Orm\Exception\FailedActionException;
use Sirius\Orm\Mapper;
use Sirius\Orm\MapperConfig;
use Sirius\Orm\Tests\Generated\Entity\EbayProduct;
use Sirius\Sql\Bindings;

/**
 * @method EbayProductQuery where($column, $value, $condition)
 * @method EbayProductQuery orderBy(string $expr, string ...$exprs)
 */
abstract class EbayProductMapperBase extends Mapper
{
    protected function init()
    {
        $this->mapperConfig = MapperConfig::fromArray([
            'entityClass' => 'Sirius\Orm\Tests\Generated\Entity\EbayProduct',
            'primaryKey' => 'id',
            'table' => 'tbl_ebay_products',
            'tableAlias' => null,
            'guards' => [],
            'columns' => ['id', 'product_id', 'price', 'is_active'],
            'columnAttributeMap' => [],
            'casts' => ['id' => 'int', 'product_id' => 'int', 'price' => 'decimal:2', 'is_active' => 'bool'],
        ]);
        $this->hydrator     = new ClassMethodsHydrator($this->orm->getCastingManager());
        $this->hydrator->setMapper($this);

        $this->initRelations();
    }

    protected function initRelations()
    {
        $this->addRelation('product', [
            'type' => 'one_to_one',
            'native_key' => 'product_id',
            'foreign_mapper' => 'products',
            'foreign_key' => 'id',
            'load_strategy' => 'lazy',
        ]);
    }

    public function find($pk, array $load = []): ?EbayProduct
    {
        return $this->newQuery()->find($pk, $load);
    }

    public function newQuery(): EbayProductQuery
    {
        $query = new EbayProductQuery($this->getReadConnection(), $this);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function newSubselectQuery(Connection $connection, Bindings $bindings, string $indent): EbayProductQuery
    {
        $query = new EbayProductQuery($this->getReadConnection(), $this, $bindings, $indent);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function save(EbayProduct $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'saving', $entity);
        $action = $this->newSaveAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'saved', $entity);

        return $result;
    }

    public function newSaveAction(EbayProduct $entity, $options): UpdateAction
    {
        if ( ! $this->getHydrator()->getPk($entity) || $entity->getState() == StateEnum::NEW) {
            $action = new InsertAction($this, $entity, $options);
        } else {
            $action = new UpdateAction($this, $entity, $options);
        }

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }

    public function delete(EbayProduct $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'deleting', $entity);
        $action = $this->newDeleteAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'deleted', $entity);

        return $result;
    }

    public function newDeleteAction(EbayProduct $entity, $options): DeleteAction
    {
        $action = new DeleteAction($this, $entity, $options);

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }
}
