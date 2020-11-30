<?php

namespace Shortcodes\Toolbox\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RelationObserver
{
    public function saving(Model $model)
    {
        $this->getRelationsFromAttributes($model);
        $this->handleRelation($model, 'saving');
    }

    public function saved(Model $model)
    {
        $this->handleRelation($model, 'saved');
    }

    private function getRelationsFromAttributes(Model $model)
    {
        $model->relationshipsInRequest = array_intersect_key($model->getAttributes(), $this->getRelationLikeProperties($model));
        $model->setRawAttributes(array_diff_key($model->getAttributes(), $model->relationshipsInRequest));
    }

    private function getRelationLikeProperties(Model $model)
    {
        return Arr::where($model->getAttributes(), function ($value, $key) use ($model) {

            foreach ($model->relationTypes as $relationName => $modelRelation) {

                if ($key === Str::snake($relationName)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function handleRelation(Model $model, $observerEvent)
    {
        $relationToHandle = [
            'saving' => ['BelongsTo'],
            'saved' => ['HasMany', 'BelongsToMany', 'HasOne', 'MorphMany'],
        ];

        foreach ($model->relationshipsInRequest as $relationName => $relationValue) {
            foreach ($model->relationTypes as $availableRelationName => $availableRelationData) {
                if ($relationName === $availableRelationName && in_array($availableRelationData['type'], $relationToHandle[$observerEvent])) {
                    $method = 'handle' . $availableRelationData['type'];
                    $this->$method($model, $relationName);
                }
            }
        }
    }

    private function handleMorphMany(Model $model, $relation)
    {
        if (Schema::hasColumn($model->$relation()->getModel()->getTable(), 'position')) {
            $position = 0;
            $modelClass = $model->$relation()->getModel();

            foreach ($model->relationshipsInRequest[$relation] as $k => $item) {
                $modelClass::find($item['id'])->forceFill(['position' => $position++])->save();
            }
        }

        if (Schema::hasColumn($model->$relation()->getModel()->getTable(), 'active')) {

            $modelClass = $model->$relation()->getModel();

            foreach ($model->relationshipsInRequest[$relation] as $k => $item) {
                $modelClass::find($item['id'])->forceFill(['active' => $item['active']])->save();
            }
        }
    }

    private function handleBelongsTo(Model $model, $relation)
    {
        $object = $model->relationshipsInRequest[$relation];
        $model->{$relation . '_id'} = is_array($object) && isset($object['id']) ? $object['id'] : $object;
    }

    private function handleHasMany(Model $model, $relation)
    {
        if (Schema::hasColumn($model->getTable(), 'position')) {
            $position = 0;

            foreach ($model->relationshipsInRequest[$relation] as $k => $item) {
                $model->relationshipsInRequest[$relation][$k]['position'] = $position++;
            }
        }

        if (isset($relation['delete'])) {

            $objectsCollection = collect($model->relationshipsInRequest[$relation['delete']]);

            $model->$relation()
                ->whereIn('id', $objectsCollection->where('id', '!=', null)->pluck('id'))
                ->delete();

            return;
        }

        if (isset($relation['detach'])) {

            $objectsCollection = collect($model->relationshipsInRequest[$relation['detach']]);

            $foreignKey = $model->getForeignKey();

            $model->$relation()
                ->whereIn('id', $objectsCollection->where('id', '!=', null)->pluck('id'))
                ->update([$foreignKey => null]);

            return;
        }

        if (isset($relation['attach'])) {


            $relatedModel = $model->$relation()->getRelated();
            $relatedObjects = $relatedModel->find(collect($model->relationshipsInRequest[$relation]['attach'])
                ->pluck('id')
            );

            $model->$relation()->saveMany($relatedObjects);

            return;
        }

        if (isset($relation['add'])) {

            $objectsCollection = collect($model->relationshipsInRequest[$relation]['add']);

            $objectsCollectionToCreate = $objectsCollection->where('id', null);
            $objectsCollectionToAttach = $objectsCollection->where('id', '!=', null);

            if ($objectsCollectionToCreate->isNotEmpty()) {
                $model->$relation()->createMany($objectsCollectionToCreate->toArray());
            }

            if ($objectsCollectionToAttach->isNotEmpty()) {
                $relatedModel = $model->$relation()->getRelated();
                $relatedObjects = $relatedModel->find($objectsCollectionToAttach->pluck('id'));
                $model->$relation()->saveMany($relatedObjects);
            }

            return;
        }

        $objectsCollection = collect($model->relationshipsInRequest[$relation]);

        $model->$relation()
            ->whereNotIn('id', $objectsCollection->where('id', '!=', null)->pluck('id'))
            ->delete();

        $objectsCollection->where('id', '!=', null)->each(function ($item) use ($model, $relation) {
            $model->$relation()->where('id', $item['id'])->first()->update($item);
        });

        $model->$relation()->createMany($objectsCollection->where('id', '=', null)->toArray());
    }

    private function handleHasOne(Model $model, $relation)
    {
        if ($model->$relation && isset($model->relationshipsInRequest[$relation]['id'])) {
            $model->$relation = $model->relationshipsInRequest[$relation]['id'];
            return;
        }

        if ($model->$relation && !$model->relationshipsInRequest[$relation]) {
            $model->$relation->delete();
            return;
        }

        if (!$model->$relation && !$model->relationshipsInRequest[$relation]) {
            return;
        }

        if (!$model->$relation) {
            $model->$relation()->create($model->relationshipsInRequest[$relation]);
            return;
        }

        $model->$relation->update($model->relationshipsInRequest[$relation]);
    }

    private static function handleBelongsToMany(Model $model, $relation)
    {
        $objectsCollection = null;
        $operation = 'sync';

        if (isset($relation['attach'])) {

            $objectsCollection = collect($model->relationshipsInRequest[$relation['attach']]);

            $operation = 'attach';

            if ($objectsCollection->isEmpty()) {
                return;
            }

            $idsAlreadyAttached = $model->$relation()
                ->whereIn($model->$relation()->getQualifiedRelatedPivotKeyName(), $objectsCollection->pluck('id'))
                ->pluck($model->$relation()->getTable() . '.' . $model->$relation()->getRelatedPivotKeyName());

            if ($idsAlreadyAttached->isNotEmpty()) {
                $objectsCollection = $objectsCollection->reject(function ($item) use ($idsAlreadyAttached, $objectsCollection, $model, $relation) {
                    return in_array($item[$model->$relation()->getRelatedPivotKeyName()], $idsAlreadyAttached->toArray());
                });
            }
        }

        if (isset($relation['detach'])) {
            $objectsCollection = collect($model->relationshipsInRequest[$relation['detach']]);

            $operation = 'detach';
        }

        if ($operation === 'sync' && Schema::hasColumn($model->$relation()->getTable(), 'position')) {
            $position = 0;

            foreach ($model->relationshipsInRequest[$relation] as $k => $item) {
                $model->relationshipsInRequest[$relation][$k]['position'] = $position++;
            }

            $objectsCollection = collect($model->relationshipsInRequest[$relation]);
        }

        $keys = $objectsCollection->keyBy('id')->map(function ($item) {
            return Arr::except($item, ['id']);
        });

        if ($operation === 'detach') {
            $keys = $objectsCollection->pluck('id')->toArray();
        }

        $model->$relation()->$operation($keys);
    }
}
