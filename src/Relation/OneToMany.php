<?php

namespace Sirius\Orm\Relation;

use Sirius\Orm\Action\BaseAction;
use Sirius\Orm\Collection\Collection;
use Sirius\Orm\Entity\EntityInterface;
use Sirius\Orm\Entity\Tracker;
use Symfony\Component\Inflector\Inflector;

class OneToMany extends Relation
{
    protected function applyDefaults(): void
    {
        $nativeKey = $this->nativeMapper->getPrimaryKey();
        if (! isset($this->options[RelationOption::NATIVE_KEY])) {
            $this->options[RelationOption::NATIVE_KEY] = $nativeKey;
        }

        if (! isset($this->options[RelationOption::FOREIGN_KEY])) {
            $prefix                                     = Inflector::singularize($this->nativeMapper->getTable());
            $this->options[RelationOption::FOREIGN_KEY] = $this->getKeyColumn($prefix, $nativeKey);
        }

        parent::applyDefaults();
    }

    public function getQuery(Tracker $tracker)
    {
        $nativeKey = $this->options[RelationOption::NATIVE_KEY];
        $nativePks = $tracker->pluck($nativeKey);

        $query = $this->foreignMapper
            ->newQuery()
            ->where($this->options[RelationOption::FOREIGN_KEY], $nativePks);

        if ($this->getOption(RelationOption::QUERY_CALLBACK) &&
            is_callable($this->getOption(RelationOption::QUERY_CALLBACK))) {
            $callback = $this->options[RelationOption::QUERY_CALLBACK];
            $query    = $callback($query);
        }

        if ($this->getOption(RelationOption::FOREIGN_GUARDS)) {
            $query->setGuards($this->options[RelationOption::FOREIGN_GUARDS]);
        }

        return $query;
    }

    public function attachMatchesToEntity(EntityInterface $nativeEntity, array $result)
    {
        $found = [];
        foreach ($result as $foreignEntity) {
            if ($this->entitiesBelongTogether($nativeEntity, $foreignEntity)) {
                $found[] = $foreignEntity;
            }
        }

        $this->nativeMapper->setEntityAttribute($nativeEntity, $this->name, new Collection($found));
    }

    protected function attachToDelete(BaseAction $action)
    {
        $nativeEntity       = $action->getEntity();
        $nativeEntityKey    = $nativeEntity->getPk();
        $remainingRelations = $this->getRemainingRelations($action->getOption('relations'));

        // no cascade delete? treat as save so we can process the changes
        if (! $this->isCascade()) {
            $this->attachToSave($action);
        } else {
            // retrieve them again from the DB since the related collection might not have everything
            // for example due to a relation query callback
            $foreignEntities = $this->getQuery(new Tracker($this->nativeMapper, [$nativeEntity->getArrayCopy()]))
                                    ->get();

            foreach ($foreignEntities as $entity) {
                $deleteAction = $this->foreignMapper
                    ->newDeleteAction($entity, ['relations' => $remainingRelations]);
                $action->append($deleteAction);
            }
        }
    }

    protected function attachToSave(BaseAction $action)
    {
        $remainingRelations = $this->getRemainingRelations($action->getOption('relations'));

        /** @var Collection $foreignEntities */
        $foreignEntities = $this->nativeMapper->getEntityAttribute($action->getEntity(), $this->name);
        $changes         = $foreignEntities->getChanges();

        // save the entities still in the collection
        foreach ($foreignEntities as $foreignEntity) {
            if (! empty($foreignEntity->getChanges())) {
                $saveAction = $this->foreignMapper
                    ->newSaveAction($foreignEntity, ['relations' => $remainingRelations]);
                $action->append($saveAction);
            }
        }

        // save entities that were removed but NOT deleted
        foreach ($changes['removed'] as $foreignEntity) {
            $saveAction = $this->foreignMapper
                ->newSaveAction($foreignEntity, ['relations' => $remainingRelations]);
            $action->append($saveAction);
        }

        // delete entities that were specifically deleted
        foreach ($changes['removed'] as $foreignEntity) {
            $saveAction = $this->foreignMapper
                ->newDeleteAction($foreignEntity, ['relations' => $remainingRelations]);
            $action->append($saveAction);
        }
    }
}
