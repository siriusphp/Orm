<?php

declare(strict_types=1);

namespace Sirius\Orm\Tests;

use Sirius\Orm\Mapper;
use Sirius\Orm\MapperConfig;

class OrmTest extends BaseTestCase
{

    public function test_lazy_mapper_config()
    {
        $mapperConfig = MapperConfig::fromArray([
            MapperConfig::TABLE       => 'products',
            MapperConfig::TABLE_ALIAS => 'p',
            MapperConfig::COLUMNS     => ['id', 'category_id', 'featured_image_id', 'sku', 'price']
        ]);
        $this->orm->register('products', $mapperConfig);

        $this->assertTrue($this->orm->has('products'));
        $this->assertInstanceOf(Mapper::class, $this->orm->get('products'));
    }

    public function test_lazy_mapper_factory()
    {
        $mapperConfig = MapperConfig::fromArray([
            MapperConfig::TABLE       => 'products',
            MapperConfig::TABLE_ALIAS => 'p',
            MapperConfig::COLUMNS     => ['id', 'category_id', 'featured_image_id', 'sku', 'price']
        ]);
        $this->orm->register('products', function ($orm) use ($mapperConfig) {
            return Mapper::make($orm, $mapperConfig);
        });

        $this->assertTrue($this->orm->has('products'));
        $this->assertInstanceOf(Mapper::class, $this->orm->get('products'));
    }

    public function test_mapper_instance()
    {
        $mapperConfig = MapperConfig::fromArray([
            MapperConfig::TABLE       => 'products',
            MapperConfig::TABLE_ALIAS => 'p',
            MapperConfig::COLUMNS     => ['id', 'category_id', 'featured_image_id', 'sku', 'price']
        ]);
        $mapper       = Mapper::make($this->orm, $mapperConfig);
        $this->orm->register('products', $mapper);

        $this->assertInstanceOf(Mapper::class, $this->orm->get('products'));
    }

    public function test_exception_thrown_on_invalid_mapper_instance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->orm->register('products', new \stdClass());
    }

    public function test_exception_thrown_on_unknown_mapper()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->orm->get('products');
    }

    public function test_exception_thrown_on_invalid_mapper_factory()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->orm->register('products', function () {
            return new \stdClass();
        });
        $this->orm->get('products');
    }
}