<?php

declare(strict_types=1);

namespace Sirius\Orm\Tests\Generated\Mapper;

use Sirius\Orm\Collection\Collection;
use Sirius\Orm\Collection\PaginatedCollection;
use Sirius\Orm\Query;
use Sirius\Orm\Query\SoftDeleteTrait;
use Sirius\Orm\Tests\Generated\Entity\CascadeProduct;

abstract class CascadeProductQueryBase extends Query
{
    use SoftDeleteTrait;

    protected $createdAtColumn = 'created_on';
    protected $updatedAtColumn = 'updated_on';
    protected $deletedAtColumn = 'deleted_on';

    public function first(): ?CascadeProduct
    {
        return parent::first();
    }

    /**
     * @return Collection|CascadeProduct[]
     */
    public function get(): Collection
    {
        return parent::get();
    }

    /**
     * @return PaginatedCollection|CascadeProduct[]
     */
    public function paginate(int $perPage, int $page = 1): PaginatedCollection
    {
        return parent::paginate($perPage, $page);
    }

    public function orderByFirstCreated()
    {
        $this->orderBy($this->createdAtColumn . ' ASC');

        return $this;
    }

    public function orderByLastCreated()
    {
        $this->orderBy($this->updatedAtColumn . ' DESC');

        return $this;
    }

    public function orderByFirstUpdated()
    {
        $this->orderBy($this->updatedAtColumn . ' ASC');

        return $this;
    }

    protected function init()
    {
        parent::init();
        $this->withoutTrashed();
    }
}
