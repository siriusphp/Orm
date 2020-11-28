<?php
declare(strict_types=1);

namespace Sirius\Orm\Contract;

interface EntityInterface
{
    public function getState();

    public function setState($state);

    public function getChanges();

    public function toArray();

    public function setLazy(string $name, LazyLoader $lazyLoader);
}
