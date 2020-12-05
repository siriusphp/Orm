<?php
declare(strict_types=1);

namespace Sirius\Orm;

use Sirius\Orm\Entity\GenericEntity;
use Sirius\Orm\Helpers\QueryHelper;

/**
 * Class MapperConfig
 * Used to create mapper definitions that can be created using Mapper::make($mapperConfigInstance)
 * This is useful for dynamically generated mappers (think Wordpress custom post types)
 * @package Sirius\Orm
 */
class MapperConfig
{
    const ENTITY_CLASS = 'entityClass';
    const PRIMARY_KEY = 'primaryKey';
    const NAME = 'name';
    const TABLE = 'table';
    const TABLE_ALIAS = 'tableAlias';
    const COLUMNS = 'columns';
    const COLUMN_ATTRIBUTE_MAP = 'columnAttributeMap';
    const CASTS = 'casts';
    const PIVOT_ATTRIBUTES = 'pivotAttributes';
    const ATTRIBUTE_DEFAULTS = 'attributeDefaults';
    const GUARDS = 'guards';

    /**
     * @var string
     */
    protected $entityClass = GenericEntity::class;

    /**
     * @var string|array
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $table;

    /**
     * Used in queries like so: FROM table as tableAlias
     * @var string
     */
    protected $tableAlias;

    /**
     * @var
     */
    protected $tableReference;

    /**
     * Table columns
     * @var array
     */
    protected $columns = [];

    /**
     * Columns casts
     * @var array
     */
    protected $casts = [];

    /**
     * Column aliases (table column => entity attribute)
     * @var array
     */
    protected $columnAttributeMap = [];

    /**
     * Attributes that might come from PIVOT_COLUMNS
     * in many-to-many relations
     * @var array
     */
    protected $pivotAttributes = [];

    /**
     * Default attributes
     * @var array
     */
    protected $attributeDefaults = [];

    /**
     * List of column-value pairs that act as global filters
     * @var array
     */
    protected $guards = [];

    public static function fromArray(array $array)
    {
        $instance = new self;
        foreach ($array as $k => $v) {
            $instance->{$k} = $v;
        }

        return $instance;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return string|array
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param bool $fallbackToTable
     *
     * @return string
     */
    public function getTableAlias($fallbackToTable = false)
    {
        return (! $this->tableAlias && $fallbackToTable) ? $this->table : $this->tableAlias;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPivotAttributes(): array
    {
        return $this->pivotAttributes;
    }


    public function getAttributeNames(): array
    {
        $columns = array_combine($this->columns, $this->columns);
        foreach ($this->getColumnAttributeMap() as $col => $attr) {
            $columns[$col] = $attr;
        }

        return array_merge(array_values($columns), $this->getPivotAttributes());
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    public function getColumnAttributeMap(): array
    {
        return $this->columnAttributeMap;
    }


    public function getAttributeDefaults(): array
    {
        return $this->attributeDefaults;
    }

    public function getGuards(): array
    {
        return $this->guards;
    }

    public function getTableReference()
    {
        if (! $this->tableReference) {
            $this->tableReference = QueryHelper::reference($this->table, $this->tableAlias);
        }

        return $this->tableReference;
    }
}
