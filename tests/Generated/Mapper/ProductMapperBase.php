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
use Sirius\Orm\Tests\Generated\Entity\Product;
use Sirius\Sql\Bindings;

/**
 * @method ProductQuery where($column, $value, $condition)
 * @method ProductQuery orderBy(string $expr, string ...$exprs)
 */
abstract class ProductMapperBase extends Mapper
{
    protected $createdAtColumn = 'created_on';
    protected $updatedAtColumn = 'updated_on';
    protected $deletedAtColumn = 'deleted_on';

    protected function init()
    {
        $this->mapperConfig = MapperConfig::fromArray([
            'entityClass' => 'Sirius\Orm\Tests\Generated\Entity\Product',
            'primaryKey' => 'id',
            'table' => 'tbl_products',
            'tableAlias' => 'products',
            'guards' => [],
            'columns' => ['id', 'category_id', 'sku', 'price', 'attributes', 'created_on', 'updated_on', 'deleted_on'],
            'columnAttributeMap' => ['price' => 'value'],
            'casts' => [
                'id' => 'int',
                'category_id' => 'int',
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
        $this->addRelation('languages', [
            'type' => 'one_to_many',
            'native_key' => 'id',
            'foreign_mapper' => 'product_languages',
            'foreign_key' => 'content_id',
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('images', [
            'type' => 'one_to_many',
            'native_key' => 'id',
            'foreign_mapper' => 'images',
            'foreign_key' => 'content_id',
            'foreign_guards' => ['content_type' => 'products'],
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('tags', [
            'type' => 'many_to_many',
            'foreign_key' => 'id',
            'pivot_table' => 'tbl_links_to_tags',
            'pivot_table_alias' => 'products_to_tags',
            'pivot_guards' => ['tagable_type' => 'products'],
            'pivot_columns' => ['position' => 'position_in_product'],
            'pivot_native_column' => 'tagable_id',
            'pivot_foreign_column' => 'tag_id',
            'aggregates' => ['tags_count' => ['function' => 'count(tags.id)']],
            'native_key' => 'id',
            'foreign_mapper' => 'tags',
            'load_strategy' => 'lazy',
            'query_callback' => function (\Sirius\Orm\Query $query) {
                $query->orderBy('position ASC');

                return $query;
            },
        ]);

        $this->addRelation('category', [
            'type' => 'many_to_one',
            'foreign_key' => 'id',
            'native_key' => 'category_id',
            'foreign_mapper' => 'categories',
            'load_strategy' => 'lazy',
        ]);

        $this->addRelation('ebay', [
            'type' => 'one_to_one',
            'native_key' => 'id',
            'foreign_mapper' => 'ebay_products',
            'foreign_key' => 'product_id',
            'load_strategy' => 'lazy',
        ]);
    }

    public function find($pk, array $load = []): ?Product
    {
        return $this->newQuery()->find($pk, $load);
    }

    public function newQuery(): ProductQuery
    {
        $query = new ProductQuery($this->getReadConnection(), $this);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function newSubselectQuery(Connection $connection, Bindings $bindings, string $indent): ProductQuery
    {
        $query = new ProductQuery($this->getReadConnection(), $this, $bindings, $indent);
        return $this->behaviours->apply($this, __FUNCTION__, $query);
    }

    public function save(Product $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'saving', $entity);
        $action = $this->newSaveAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'saved', $entity);

        return $result;
    }

    public function newSaveAction(Product $entity, $options): UpdateAction
    {
        if ( ! $this->getHydrator()->getPk($entity) || $entity->getState() == StateEnum::NEW) {
            $action = new InsertAction($this, $entity, $options);
        } else {
            $action = new UpdateAction($this, $entity, $options);
        }

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }

    public function delete(Product $entity, $withRelations = false): bool
    {
        $entity = $this->behaviours->apply($this, 'deleting', $entity);
        $action = $this->newDeleteAction($entity, ['relations' => $withRelations]);
        $result = $this->runActionInTransaction($action);
        $entity = $this->behaviours->apply($this, 'deleted', $entity);

        return $result;
    }

    public function newDeleteAction(Product $entity, $options)
    {
        $options = array_merge((array) $options, ['deleted_at_column' => $this->deletedAtColumn]);
        $action = new SoftDeleteAction($this, $entity, $options);

        return $this->behaviours->apply($this, __FUNCTION__, $action);
    }

    public function forceDelete(Product $entity, $withRelations = false)
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
