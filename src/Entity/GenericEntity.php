<?php
declare(strict_types=1);

namespace Sirius\Orm\Entity;

class GenericEntity implements EntityInterface
{
    protected $state = StateEnum::CHANGED;

    protected $primaryKey = 'id';

    protected $attributes = [];

    protected $lazyLoaders = [];

    protected $changed = [];

    public function __construct(array $attributes)
    {
        foreach ($attributes as $attr => $value) {
            $this->set($attr, $value);
        }
    }

    public function getPk()
    {
        /**
         * @todo implement a way to retrieve the proper PK columns
         */
        return $this->get($this->primaryKey);
    }

    public function setPk($val)
    {
        /**
         * @todo implement a way to retrieve the proper PK columns
         */
        $this->set($this->primaryKey, $val);
    }

    public function set($attributeOrAttributes, $value = null)
    {
        $this->preventChangesIfDeleted();
        if (is_array($attributeOrAttributes) && $value == null) {
            foreach ($attributeOrAttributes as $k => $v) {
                $this->set($k, $v);
            }

            return $this;
        }

        if ($value instanceof LazyValueLoader) {
            $this->lazyLoaders[$attributeOrAttributes] = $value;

            return $this;
        }

        if (isset($this->attributes[$attributeOrAttributes]) && $value != $this->attributes[$attributeOrAttributes]) {
            $this->changed[$attributeOrAttributes] = true;
            $this->state                           = StateEnum::CHANGED;
        }
        $this->attributes[$attributeOrAttributes] = $value;

        return $this;
    }

    public function get($attribute)
    {
        if (! $attribute) {
            return null;
        }

        $this->maybeLazyLoad($attribute);

        return $this->attributes[$attribute] ?? null;
    }

    public function getPersistanceState()
    {
        if (! empty($this->changed)) {
        }

        return $this->state;
    }

    public function setPersistanceState($state)
    {
        if ($state == StateEnum::SYNCHRONIZED) {
            $this->changed = [];
        }
        $this->state = $state;
    }

    public function getArrayCopy()
    {
        return $this->attributes;
    }

    public function getChanges()
    {
        $changes = $this->changed;
        foreach ($this->attributes as $name => $value) {
            if (is_object($value) && method_exists($value, 'getChanges')) {
                if (! empty($value->getChanges())) {
                    $changes[$name] = true;
                }
            }
        }

        return $changes;
    }

    protected function preventChangesIfDeleted()
    {
        if ($this->state == StateEnum::DELETED) {
            throw new \BadMethodCallException('Entity was deleted, no further changes are allowed');
        }
    }

    /**
     * @param $attribute
     */
    protected function maybeLazyLoad($attribute): void
    {
        if (isset($this->lazyLoaders[$attribute])) {
            /** @var LazyValueLoader $lazyLoader */
            $lazyLoader = $this->lazyLoaders[$attribute];
            $lazyLoader->load();
            unset($this->changed[$attribute]);
            unset($this->lazyLoaders[$attribute]);
        }
    }
}
