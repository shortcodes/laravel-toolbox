<?php

namespace Shortcodes\Toolbox\Traits\Crudable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

trait ResourceManagement
{
    public function retrieveResource($object)
    {
        if ($object instanceof Collection && $this->listResource) {
            return $this->listResource::collect($object);
        }

        if ($object instanceof Collection && $this->objectResource) {
            return $this->objectResource::collect($object);
        }

        if ($object instanceof Model && $this->objectResource) {
            return new $this->objectResource($object);
        }

        if ($object instanceof Collection) {
            return JsonResource::collection($object);
        }

        return $object;
    }
}
