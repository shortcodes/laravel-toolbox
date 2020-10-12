<?php

namespace Shortcodes\Toolbox\Traits\Crudable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait ResourceManagement
{
    public function retrieveResource($object)
    {
        if (($object instanceof Collection || $object instanceof LengthAwarePaginator) && isset($this->listResource)) {
            return $this->listResource::collection($object);
        }

        if ($object instanceof Collection && isset($this->objectResource)) {
            return $this->objectResource::collect($object);
        }

        if ($object instanceof Model && isset($this->objectResource)) {
            return new $this->objectResource($object);
        }

        if ($object instanceof Collection) {
            return JsonResource::collection($object);
        }

        return $object;
    }
}
