<?php

namespace Shortcodes\Toolbox\Traits\Crudable;

use Illuminate\Support\Facades\DB;

trait Crudable
{
    use ResourceManagement, RequestManagement, SearchManagement;

    public function index()
    {
        $this->applyRequest();

        $resource = $this->retrieveResource(
            $this->search()
        );

        if (method_exists($this, 'prepareMeta')) {
            $resource->additional(['meta' => $this->addMeta()]);
        }

        return $resource;
    }

    public function store()
    {
        $this->applyRequest();

        return DB::transaction(function () {
            return $this->retrieveResource(
                $this->model::create(request()->all())
            );
        });
    }

    public function show($objectId)
    {
        $this->applyRequest();

        $object = $this->model::findOrFail($objectId);

        return $this->retrieveResource($object);
    }

    public function update($objectId)
    {
        $this->applyRequest();

        $object = $this->model::findOrFail($objectId);

        return DB::transaction(function () use ($object) {
            return $this->retrieveResource(
                tap($object)->update(request()->all())
            );
        });
    }

    public function destroy($objectId)
    {
        $this->applyRequest();

        $object = $this->model::findOrFail($objectId);

        DB::transaction(function () use ($object) {
            $object->delete();
        });

        return response()->noContent();
    }

}
