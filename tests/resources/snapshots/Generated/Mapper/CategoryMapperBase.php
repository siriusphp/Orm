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
use Sirius\Orm\Tests\Generated\Entity\Category;
use Sirius\Sql\Bindings;

/**
 * @method CategoryQuery where($column, $value, $condition)
 * @method CategoryQuery orderBy(string $expr, string ...$exprs)
 */
abstract class CategoryMapperBase extends Mapper
{
    protected function init()
    {
        $this->mapperConfig = MapperConfig::fromArray([
            'entityClass' => 'Sirius\Orm\Tests\Generated\Entity\Category',
            'primaryKey' => 'id',
            'table' => 'categories',
            'tableAlias' => null,
            'guards' => [],
            'columns' => ['id', 'parent_id', 'position', 'name'],
            'columnAttributeMap' => [],
            'casts' => ['id' => 'int', 'parent_id' => 'int', 'position' => 'int', 'name' => 'string'],
        ]);
        $this->hydrator     = new ClassMethodsHydrator($this->orm->getCastingManager());
        $this->hydrator->setMapper($this);

        $this->initRelations();
    }

    protected function initRelations()
    {
        $this->addRelation('parent', [
            'type' => 'many_to_one',
            'foreign_key' => 'id',
            'native_key' => 'parent_id',
            'foreign_mapper' => 'categories',
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('children', [
            'type' => 'one_to_many',
            'cascade' => true,
            'native_key' => 'id',
            'foreign_mapper' => 'categories',
            'foreign_key' => 'parent_id',
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('languages', [
            'type' => 'one_to_many',
            'cascade' => true,
            'native_key' => 'id',
            'foreign_mapper' => 'languages',
            'foreign_key' => 'content_id',
            'foreign_guards' => ['content_type' => 'categories'],
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('products', [
            'type' => 'one_to_many',
            'aggregates' => [
                'lowest_price' => ['function' => 'min(products.price)'],
                'highest_price' => ['function' => 'max(products.price)'],
            ],
            'native_key' => 'id',
            'foreign_mapper' => 'products',
            'foreign_key' => 'category_id',
            'load_strategy' => 'lazy',
        ]);
    }

    public function find($pk, array $load = []): ?Category
    {
        return $this->newQuery()->find($pk, $load);
    }

    public function newQuery(): CategoryQuery
    {
        $query = new CategoryQuery($this->getReadConnection(), $this);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function newSubselectQuery(Connection $connection, Bindings $bindings, string $indent): CategoryQuery
    {
        $query = new CategoryQuery($this->getReadConnection(), $this, $bindings, $indent);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function save(Category $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'saving', $entity);
        $action = $this->newSaveAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'saved', $entity);

        return $result;
    }

    public function newSaveAction(Category $entity, $options): UpdateAction
    {
        if ( ! $this->getHydrator()->getPk($entity) || $entity->getState() == StateEnum::NEW) {
            $action = new InsertAction($this, $entity, $options);
        } else {
            $action = new UpdateAction($this, $entity, $options);
        }

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }

    public function delete(Category $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'deleting', $entity);
        $action = $this->newDeleteAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'deleted', $entity);

        return $result;
    }

    public function newDeleteAction(Category $entity, $options): DeleteAction
    {
        $action = new DeleteAction($this, $entity, $options);

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }
}
