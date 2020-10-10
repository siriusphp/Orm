<?php
declare(strict_types=1);

namespace Sirius\Orm\Tests\Behaviour;

use Sirius\Orm\Behaviour\SoftDelete;
use Sirius\Orm\Behaviour\Timestamps;
use Sirius\Orm\Mapper;
use Sirius\Orm\MapperConfig;
use Sirius\Orm\Tests\BaseTestCase;

class TimestampsTest extends BaseTestCase
{
    /**
     * @var Mapper
     */
    protected $mapper;

    public function test_behaviour_is_applied()
    {
        // create a clone so the ORM is not affected
        $mapper = $this->orm->get('products')->without();
        $mapper->use(new Timestamps());

        $product = $mapper->newEntity(['title' => 'Product 1']);

        $this->assertNull($product->created_at);
        $this->assertNull($product->updated_at);

        $this->assertTrue($mapper->save($product));

        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
    }
}
