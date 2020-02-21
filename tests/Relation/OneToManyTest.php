<?php
declare(strict_types=1);

namespace Sirius\Orm\Tests\Relation;

use Sirius\Orm\Collection\Collection;
use Sirius\Orm\Entity\Tracker;
use Sirius\Orm\Mapper;
use Sirius\Orm\Query;
use Sirius\Orm\Relation\OneToMany;
use Sirius\Orm\Relation\RelationConfig;
use Sirius\Orm\Tests\BaseTestCase;

class OneToManyTest extends BaseTestCase
{

    /**
     * @var Mapper
     */
    protected $nativeMapper;
    /**
     * @var Mapper
     */
    protected $foreignMapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadMappers();

        $this->nativeMapper  = $this->orm->get('categories');
        $this->foreignMapper = $this->orm->get('products');
    }

    public function test_query_callback()
    {
        $relation = new OneToMany('products', $this->nativeMapper, $this->foreignMapper, [
            RelationConfig::QUERY_CALLBACK => function (Query $query) {
                return $query->where('deleted_at', null);
            }
        ]);

        $tracker = new Tracker($this->nativeMapper, [
            ['id' => 10],
            ['id' => 11],
        ]);
        $query   = $relation->getQuery($tracker);

        $expectedSql = <<<SQL
SELECT
    products.*
FROM
    products
WHERE
    category_id IN (:__1__, :__2__) AND deleted_at IS NULL
SQL;

        $this->assertSameStatement($expectedSql, $query->getStatement());
        $this->assertSame([
            '__1__' => [10, \PDO::PARAM_INT],
            '__2__' => [11, \PDO::PARAM_INT],
        ], $query->getBindValues());
    }

    public function test_join_with() {
        $query = $this->nativeMapper->newQuery()
            ->joinWith('products');

        $expectedStatement = <<<SQL
SELECT
    categories.*
FROM
    categories
    INNER JOIN (
    SELECT
        products.*
    FROM
        products
    ) AS products ON categories.id = products.category_id
SQL;

        $this->assertSameStatement($expectedStatement, $query->getStatement());
    }

    public function test_query_guards()
    {
        $relation = new OneToMany('products', $this->nativeMapper, $this->foreignMapper, [
            RelationConfig::FOREIGN_GUARDS => ['status' => 'active', 'deleted_at IS NULL']
        ]);

        $tracker = new Tracker($this->nativeMapper, [
            ['id' => 10],
            ['id' => 11],
        ]);
        $query   = $relation->getQuery($tracker);

        $expectedSql = <<<SQL
SELECT
    products.*
FROM
    products
WHERE
    (category_id IN (:__1__, :__2__)) AND status = :__3__ AND deleted_at IS NULL
SQL;

        $this->assertSameStatement($expectedSql, $query->getStatement());
        $this->assertSame([
            '__1__' => [10, \PDO::PARAM_INT],
            '__2__' => [11, \PDO::PARAM_INT],
            '__3__' => ['active', \PDO::PARAM_STR],
        ], $query->getBindValues());
    }

    public function test_eager_load()
    {
        $this->populateDb();

        $category = $this->nativeMapper
            ->newQuery()
            ->load('products')
            ->first();

        $this->assertEquals(3, count($category->get('products')));
    }

    public function test_lazy_load()
    {
        $this->populateDb();

        $category = $this->nativeMapper
            ->newQuery()
            ->first();

        $this->assertEquals(3, count($category->get('products')));
    }

    public function test_delete_with_cascade_true()
    {
        $this->populateDb();
        // reconfigure products-featured_image to use CASCADE
        $config                                                 = $this->getMapperConfig('categories');
        $config->relations['products'][RelationConfig::CASCADE] = true;
        $this->nativeMapper                                     = $this->orm->register('categories', $config)->get('categories');

        $category = $this->nativeMapper
            ->newQuery()
            ->first();
        $product = $category->get('products')[0];

        $this->assertTrue($this->nativeMapper->delete($category, true));
        $this->assertNull($category->getPk());
        $this->assertNotNull($product->getPk()); // related entities are not deleted via the relations bc possible relation query callback
        $this->assertRowDeleted('products', 'id', 1);
        $this->assertRowDeleted('products', 'id', 2);
    }

    public function test_delete_with_cascade_false()
    {
        $this->populateDb();

        $category = $this->nativeMapper
            ->newQuery()
            ->first();
        /** @var Collection $products */
        $products = $category->get('products');
        $products[0]->set('sku', 'sku_1');
        $products->removeElement($products[1]);

        $this->assertTrue($this->nativeMapper->delete($category, true));
        // check if the first product was updated
        $product = $this->foreignMapper->find($products[0]->getPk());
        $this->assertNotNull($product);
        $this->assertEquals('sku_1', $product->get('sku'));
    }

    public function test_insert()
    {
        $this->populateDb();

        $category = $this->nativeMapper->newEntity([
            'name' => 'New category',
            'products' => new Collection()
        ]);
        $product = $this->foreignMapper->newEntity([
            'sku' => 'New sku'
        ]);
        /** @var Collection $products */
        $products = $category->get('products');
        $products->add($product);

        $this->nativeMapper->save($category);
        $this->assertEquals($category->getPk(), $product->get('category_id'));
    }

    public function test_save_with_relations()
    {
        $this->populateDb();

        $category = $this->nativeMapper
            ->newQuery()
            ->first();
        /** @var Collection $products */
        $products = $category->get('products');
        $products[0]->set('sku', 'sku_1');

        $this->nativeMapper->save($category);
        $product = $this->foreignMapper->find($products[0]->getPk());
        $this->assertEquals('sku_1', $product->get('sku'));
    }

    public function test_save_without_relations()
    {
        $this->populateDb();

        $category = $this->nativeMapper
            ->newQuery()
            ->first();
        /** @var Collection $products */
        $products = $category->get('products');
        $products[0]->set('sku', 'sku_1');

        $this->nativeMapper->save($category, false);
        $product = $this->foreignMapper->find($products[0]->getPk());
        $this->assertEquals('abc', $product->get('sku'));
    }

    protected function populateDb(): void
    {
        $this->insertRow('categories', ['id' => 10, 'name' => 'Category']);
        $this->insertRow('products', ['id' => 1, 'category_id' => 10, 'sku' => 'abc', 'price' => 10]);
        $this->insertRow('products', ['id' => 2, 'category_id' => 10, 'sku' => 'xyz', 'price' => 30]);
        $this->insertRow('products', ['id' => 3, 'category_id' => 10, 'sku' => 'qwe', 'price' => 20]);
    }
}