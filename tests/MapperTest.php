<?php
declare(strict_types=1);

namespace Sirius\Orm\Tests;

use Sirius\Orm\Mapper;
use Sirius\Orm\MapperConfig;
use Sirius\Orm\Tests\Entity\ProductEntity;

class MapperTest extends BaseTestCase
{
    /**
     * @var Mapper
     */
    protected $mapper;

    public function setUp(): void
    {
        parent::setUp();

        $this->mapper = Mapper::make($this->orm, MapperConfig::fromArray([
            MapperConfig::TABLE        => 'products',
            MapperConfig::ENTITY_CLASS => ProductEntity::class,
            MapperConfig::TABLE_ALIAS  => 'p',
            MapperConfig::COLUMNS      => ['id', 'category_id', 'featured_image_id', 'sku', 'price'],
            MapperConfig::COLUMN_ATTRIBUTE_MAP => ['price' => 'value']
        ]));
    }

    public function test_new_entity()
    {
        $product = $this->mapper->newEntity([
            'category_id' => '10',
            'featured_image_id' => '20',
            'sku' => 'sku 1',
            'price' => '100.343'
        ]);

        $this->assertEquals(100.34, $product->get('value'));
        $this->assertEquals(10, $product->get('category_id'));
        $this->assertEquals(20, $product->get('featured_image_id'));
    }
}