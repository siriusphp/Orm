<?php
declare(strict_types=1);

namespace Sirius\Orm\Action;

use Sirius\Orm\Entity\StateEnum;

class SoftDelete extends Delete
{
    /**
     * @var int
     */
    protected $now;

    protected function execute()
    {
        $entityId = $this->getEntityHydrator()->getPk($this->entity);
        if ( ! $entityId) {
            return;
        }

        $this->now = time();

        $update = new \Sirius\Sql\Update($this->mapper->getWriteConnection());
        $update->table($this->mapper->getConfig()->getTable())
               ->columns([
                   $this->getOption('deleted_at_column') => $this->now
               ])
               ->where('id', $entityId);
        $update->perform();
    }

    public function onSuccess()
    {
        $this->getEntityHydrator()->set($this->entity, $this->getOption('deleted_at_column'), $this->now);
        if ($this->entity->getState() !== StateEnum::DELETED) {
            $this->entity->setState(StateEnum::DELETED);
        }
    }
}
