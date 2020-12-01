<?php
declare(strict_types=1);

namespace Sirius\Orm\Tests\DbTests\Base\Relation;

use Sirius\Orm\Entity\Tracker;
use Sirius\Orm\Mapper;
use Sirius\Orm\Query;
use Sirius\Orm\Relation\ManyToOne;
use Sirius\Orm\Relation\RelationConfig;
use Sirius\Orm\Tests\BaseTestCase;
use Sirius\Orm\Tests\Generated\Entity\Category;
use Sirius\Orm\Tests\Generated\Entity\Product;
use Sirius\Orm\Tests\Generated\Mapper\CategoryMapper;
use Sirius\Orm\Tests\Generated\Mapper\ProductMapper;

class ManyToOneTest extends BaseTestCase
{

    /**
     * @var ProductMapper
     */
    protected $productsMapper;
    /**
     * @var CategoryMapper
     */
    protected $categoriesMapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->loadMappers();

        $this->productsMapper   = $this->orm->get('products');
        $this->categoriesMapper = $this->orm->get('categories');
    }

    public function test_join_with()
    {
        $query = $this->productsMapper->newQuery()
                                      ->joinWith('category');

        $expectedStatement = <<<SQL
SELECT
    products.*
FROM
    tbl_products as products
        INNER JOIN     (
    SELECT
        categories.*
    FROM
        categories
    ) AS category ON products.category_id = category.id
    WHERE deleted_on IS NULL
SQL;

        $this->assertSameStatement($expectedStatement, $query->getStatement());
    }

    public function test_query_callback()
    {
        $relation = new ManyToOne('category', $this->productsMapper, $this->categoriesMapper, [
            RelationConfig::QUERY_CALLBACK => function (Query $query) {
                return $query->where('status', 'active');
            }
        ]);

        $tracker = new Tracker([
            ['category_id' => 10],
            ['category_id' => 11],
        ]);
        $query   = $relation->getQuery($tracker);

        $expectedSql = <<<SQL
SELECT
    categories.*
FROM
    categories
WHERE
    id IN (:__1__, :__2__) AND status = :__3__
SQL;

        $this->assertSameStatement($expectedSql, $query->getStatement());
        $this->assertSame([
            '__1__' => [10, \PDO::PARAM_INT],
            '__2__' => [11, \PDO::PARAM_INT],
            '__3__' => ['active', \PDO::PARAM_STR],
        ], $query->getBindValues());
    }

    public function test_query_guards()
    {
        $relation = new ManyToOne('category', $this->productsMapper, $this->categoriesMapper, [
            RelationConfig::FOREIGN_GUARDS => ['status' => 'active', 'deleted_at IS NULL']
        ]);

        $tracker = new Tracker([
            ['category_id' => 10],
            ['category_id' => 11],
        ]);
        $query   = $relation->getQuery($tracker);

        $expectedSql = <<<SQL
SELECT
    categories.*
FROM
    categories
WHERE
    (id IN (:__1__, :__2__)) AND status = :__3__ AND deleted_at IS NULL
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

        $products = $this->productsMapper
            ->newQuery()
            ->load('category', 'category.parent')
            ->get();

        $this->assertExpectedQueries(3); // products + category + category parent
        $category1 = $products[0]->category;
        $category2 = $products[1]->category;
        $this->assertNotNull($category1);
        $this->assertEquals(20, $category1->getId());
        $this->assertNotNull($category2);
        $this->assertSame($category1, $category2); // to ensure only one query was executed
        $this->assertSame($category1->getParent(), $category2->getParent()); // to ensure only one query was executed
        $this->assertExpectedQueries(3); // products + category + category parent
    }

    public function test_lazy_load()
    {
        $this->populateDb();

        $products = $this->productsMapper
            ->newQuery()
            ->get();

        $this->assertExpectedQueries(1); // products + category + category parent
        $category1 = $products[0]->category;
        $category2 = $products[1]->category;
        $this->assertNotNull($category1);
        $this->assertEquals(20, $category1->getId());
        $this->assertNotNull($category2);
        $this->assertSame($category1, $category2); // to ensure only one query was executed
        $this->assertSame($category1->getParent(), $category2->getParent()); // to ensure only one query was executed
        $this->assertExpectedQueries(3); // products + category + category parent
    }

    public function test_insert_with_relations()
    {
        $this->populateDb();

        $product = $this->productsMapper->newEntity([
            'sku'   => 'New sku',
            'price' => 5,
        ]);

        $category = $this->categoriesMapper->newEntity([
            'name' => 'New Category'
        ]);

        $product->category = $category;

        $this->productsMapper->save($product, true);
        $this->assertEquals($category->getId(), $product->category_id);
    }

    public function test_save_with_relations()
    {
        $this->populateDb();

        $product = $this->productsMapper
            ->newQuery()
            ->first();

        $category       = $product->category;
        $category->setName('New category');

        $this->productsMapper->save($product, true);
        $category = $this->categoriesMapper->find($category->getId());
        $this->assertEquals('New category', $category->getName());
    }

    public function test_save_with_relations_after_patching()
    {
        $this->populateDb();

        $product = $this->productsMapper
            ->newQuery()
            ->first();

        $this->productsMapper->patch($product, [
           'category' => ['name' => 'New category']
        ]);

        $this->productsMapper->save($product, true);

        $product = $this->productsMapper->find($product->id);

        $this->assertEquals('New category', $product->category->getName());

        $queries = $this->getQueryCount();

        // remove the property
        $this->productsMapper->patch($product, [
            'category' => null
        ]);
        $this->productsMapper->save($product, true);

        // one query for updating the product
        $this->assertExpectedQueries($queries + 1, 1);

        $product = $this->productsMapper->find($product->id);

        $this->assertNull($product->category);
    }

    public function test_save_without_relations()
    {
        $this->populateDb();

        /** @var Product $product */
        $product = $this->productsMapper
            ->newQuery()
            ->first();

        $category       = $product->category;
        $category->setName('New category');

        $this->productsMapper->save($product, false);
        $category = $this->categoriesMapper->find($category->getId());
        $this->assertEquals('Category', $category->getName());
    }

    protected function populateDb(): void
    {
        $this->insertRow('categories', ['id' => 10, 'name' => 'Parent']);
        $this->insertRow('categories', ['id' => 20, 'parent_id' => 10, 'name' => 'Category']);

        $this->insertRow('tbl_products', ['id' => 1, 'category_id' => 20, 'sku' => 'abc', 'price' => 10.5]);
        $this->insertRow('tbl_products', ['id' => 2, 'category_id' => 20, 'sku' => 'xyz', 'price' => 20.5]);
    }
}
